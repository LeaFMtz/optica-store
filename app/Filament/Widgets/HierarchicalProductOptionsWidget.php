<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\ProductOption;
use Awcodes\Shout\Components\Shout;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lunar\Admin\Events\ProductVariantOptionsUpdated;
use Lunar\Admin\Filament\Resources\ProductResource\Widgets\ProductOptionsWidget;
use Lunar\Utils\Arr;

class HierarchicalProductOptionsWidget extends ProductOptionsWidget
{
    protected string $view = 'vendor.lunarpanel.resources.product-resource.widgets.product-options';

    // -------------------------------------------------------------------------
    // Task 2.2 + 2.3 + 2.4 — addSharedOptionAction override
    // -------------------------------------------------------------------------

    public function addSharedOptionAction(): Action
    {
        $existing = collect($this->configuredOptions)->pluck('id');

        $options = ProductOption::whereNotIn('id', $existing)
            ->shared()
            ->get();

        $parentOptionIds = ProductOption::whereHas('children')->pluck('id')->flip();

        return Action::make('addSharedOption')
            ->schema([
                Shout::make('no_shared_components')
                    ->content(
                        __('lunarpanel::productoption.widgets.product-options.actions.add-shared-option.form.no_shared_components.label'),
                    )
                    ->visible($options->isEmpty()),

                Select::make('product_option')
                    ->options(
                        fn () => $options->mapWithKeys(
                            fn ($option) => [$option->id => $option->translate('name')],
                        ),
                    )
                    ->label(
                        __('lunarpanel::productoption.widgets.product-options.actions.add-shared-option.form.product_option.label'),
                    )
                    ->visible($options->isNotEmpty())
                    ->live(),

                // Standard preselect toggle — hidden when a hierarchical option is selected
                Toggle::make('preselect')
                    ->default(true)
                    ->label(
                        __('lunarpanel::productoption.widgets.product-options.actions.add-shared-option.form.preselect.label'),
                    )
                    ->visible(
                        $options->isNotEmpty(),
                    )
                    ->hidden(fn (Get $get) => $this->selectedOptionIsHierarchical($get('product_option'), $parentOptionIds)),

                // Hierarchical selection — visible only when a parent option is selected
                CheckboxList::make('selections')
                    ->label('Combinaciones de variantes')
                    ->options(fn (Get $get) => $this->buildCompositeOptions((int) $get('product_option')))
                    ->columns(2)
                    ->hidden(fn (Get $get) => !$this->selectedOptionIsHierarchical($get('product_option'), $parentOptionIds)),
            ])
            ->action(function (array $data) use ($parentOptionIds) {
                $optionId = (int) $data['product_option'];
                $parentOption = ProductOption::with(['values', 'children.values'])->find($optionId);

                if ($parentOptionIds->has($optionId)) {
                    $this->addHierarchicalOption($parentOption, $data['selections'] ?? []);
                } else {
                    $this->configuredOptions[] = $this->mapOption(
                        $parentOption,
                        $parentOption->values->map(
                            fn ($value) => $this->mapOptionValue($value, $data['preselect'] ?? false),
                        )->toArray(),
                    );
                }
            })
            ->after(
                fn () => ProductVariantOptionsUpdated::dispatch($this->record),
            );
    }

    // -------------------------------------------------------------------------
    // Task 3.2 — configureBaseOptions override
    // -------------------------------------------------------------------------

    public function configureBaseOptions(): void
    {
        parent::configureBaseOptions();

        $configuredIds = collect($this->configuredOptions)->pluck('id')->filter()->flip();

        // Detect parent options already in configuredOptions whose children are also configured
        $toRemove = [];
        $toAdd = [];

        foreach ($this->configuredOptions as $index => $entry) {
            if (empty($entry['id'])) {
                continue;
            }

            $dbOption = ProductOption::find($entry['id']);

            if (!$dbOption || !$dbOption->parent_id) {
                continue;
            }

            // This entry is a child of another configured option
            if ($configuredIds->has($dbOption->parent_id)) {
                $parentEntry = collect($this->configuredOptions)->firstWhere('id', $dbOption->parent_id);

                if ($parentEntry) {
                    $selections = $this->inferParentValueSelections($dbOption->parent_id, $dbOption->id);

                    $toRemove[] = $index;
                    $toAdd[] = array_merge($entry, [
                        'parent_key' => "option_{$dbOption->parent_id}",
                        'parent_value_selections' => $selections,
                    ]);

                    // Mark parent as is_parent
                    foreach ($this->configuredOptions as $idx => $e) {
                        if (isset($e['id']) && (int) $e['id'] === (int) $dbOption->parent_id) {
                            $this->configuredOptions[$idx]['is_parent'] = true;
                            break;
                        }
                    }
                }
            }
        }

        // Remove original child entries and re-add as hierarchical
        foreach ($toRemove as $index) {
            unset($this->configuredOptions[$index]);
        }

        $this->configuredOptions = array_values($this->configuredOptions);

        foreach ($toAdd as $childEntry) {
            $this->configuredOptions[] = $childEntry;
        }
    }

    // -------------------------------------------------------------------------
    // Task 3.5 — mapVariantPermutations override
    // -------------------------------------------------------------------------

    public function mapVariantPermutations($fillMissing = true): void
    {
        $existingVariants = $this->record->variants
            ->load(['basePrices.currency', 'basePrices.priceable', 'values.option'])
            ->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->basePrices->first()?->price->decimal ?: 0,
                'stock' => $variant->stock,
                'values' => $variant->values->mapWithKeys(
                    fn ($value) => [$value->option->translate('name') => $value->translate('name')],
                )->toArray(),
            ])->toArray();

        // Collect keys that are children
        $childKeys = collect($this->configuredOptions)
            ->filter(fn ($opt) => !empty($opt['parent_key']))
            ->pluck('parent_key')
            ->flip();

        $hierarchicalPairs = [];
        $independentOptions = [];

        foreach ($this->configuredOptions as $option) {
            if (!empty($option['parent_key'])) {
                // Find parent by key
                $parent = collect($this->configuredOptions)->firstWhere('key', $option['parent_key']);
                if ($parent) {
                    $hierarchicalPairs[] = ['parent' => $parent, 'child' => $option];
                }

                continue;
            }

            if ($childKeys->has($option['key'] ?? '')) {
                // This is a parent of a hierarchical pair — skip, handled above
                continue;
            }

            // Independent option
            $enabledValues = collect($option['option_values'])
                ->filter(fn ($v) => $v['enabled'])
                ->map(fn ($v) => $v['value'])
                ->values()
                ->toArray();

            if ($option['value'] && count($enabledValues)) {
                $independentOptions[$option['value']] = $enabledValues;
            }
        }

        // No hierarchical pairs — delegate to Lunar's original implementation
        if (empty($hierarchicalPairs)) {
            parent::mapVariantPermutations($fillMissing);

            return;
        }

        // Build hierarchical permutations
        $allPermutations = [];

        foreach ($hierarchicalPairs as $pair) {
            $perms = $this->buildHierarchicalPermutations($pair['parent'], $pair['child']);
            $allPermutations = array_merge($allPermutations, $perms);
        }

        // Combine with independent options
        if (!empty($independentOptions)) {
            $allPermutations = $this->combineWithIndependent($allPermutations, $independentOptions);
        }

        $this->variants = $this->matchPermutationsToVariants($allPermutations, $existingVariants, $fillMissing);
    }

    // -------------------------------------------------------------------------
    // Task 3.1 — inferParentValueSelections
    // -------------------------------------------------------------------------

    private function inferParentValueSelections(int $parentOptionId, int $childOptionId): array
    {
        $variants = $this->record->variants()
            ->with(['values' => fn ($q) => $q->whereIn('product_option_id', [$parentOptionId, $childOptionId])])
            ->get();

        $selections = [];

        foreach ($variants as $variant) {
            $parentValue = $variant->values->firstWhere('product_option_id', $parentOptionId);
            $childValue = $variant->values->firstWhere('product_option_id', $childOptionId);

            if (!$parentValue || !$childValue) {
                continue;
            }

            $parentName = $parentValue->translate('name');
            $childName = $childValue->translate('name');

            if (!isset($selections[$parentName])) {
                $selections[$parentName] = [];
            }

            if (!in_array($childName, $selections[$parentName])) {
                $selections[$parentName][] = $childName;
            }
        }

        return $selections;
    }

    // -------------------------------------------------------------------------
    // Task 3.3 — buildHierarchicalPermutations
    // -------------------------------------------------------------------------

    private function buildHierarchicalPermutations(array $parentEntry, array $childEntry): array
    {
        $permutations = [];
        $parentOptionName = $parentEntry['value'];
        $childOptionName = $childEntry['value'];
        $selections = $childEntry['parent_value_selections'] ?? [];

        foreach ($parentEntry['option_values'] as $parentValue) {
            if (!$parentValue['enabled']) {
                continue;
            }

            $parentValueName = $parentValue['value'];
            $selectedChildValues = $selections[$parentValueName] ?? [];

            foreach ($selectedChildValues as $childValueName) {
                $permutations[] = [
                    $parentOptionName => $parentValueName,
                    $childOptionName => $childValueName,
                ];
            }
        }

        return $permutations;
    }

    // -------------------------------------------------------------------------
    // Task 3.4 — matchPermutationsToVariants (replicates Lunar's matching logic)
    // -------------------------------------------------------------------------

    private function matchPermutationsToVariants(array $permutations, array $existingVariants, bool $fillMissing): array
    {
        $variantPermutations = [];

        foreach ($permutations as $permutation) {
            $variantIndex = collect($existingVariants)->search(function ($variant) use ($permutation) {
                $valueDifference = array_diff_assoc($permutation, $variant['values']);

                if (!count($valueDifference)) {
                    return $variant;
                }

                $amountMatched = count($permutation) - count($valueDifference);

                return $amountMatched == count($variant['values']);
            });

            $variant = $existingVariants[$variantIndex] ?? null;
            $variantId = $variant['id'] ?? null;
            $sku = $variant['sku'] ?? null;
            $copiedFrom = null;
            $shouldFill = true;

            if ($variant) {
                $existing = collect($variantPermutations)->first(
                    fn ($p) => $p['variant_id'] == $variant['id'],
                );

                if ($existing) {
                    $diff = array_diff_assoc($permutation, $variant['values']);
                    $sku = $existing['sku'].'-'.implode('-', array_values($diff));
                    $variantId = null;
                    $copiedFrom = $variant['id'];
                }

                if ($existing && !$fillMissing) {
                    $shouldFill = false;
                }
            }

            if ($shouldFill) {
                $variantPermutations[] = [
                    'key' => Str::random(),
                    'variant_id' => $variantId,
                    'copied_id' => $copiedFrom,
                    'sku' => $sku,
                    'price' => $variant['price'] ?? 0,
                    'stock' => $variant['stock'] ?? 0,
                    'values' => $permutation,
                ];
            }
        }

        return $variantPermutations;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function selectedOptionIsHierarchical(mixed $optionId, Collection $parentOptionIds): bool
    {
        if (!$optionId) {
            return false;
        }

        return $parentOptionIds->has((int) $optionId);
    }

    private function buildCompositeOptions(int $optionId): array
    {
        if (!$optionId) {
            return [];
        }

        $parentOption = ProductOption::with(['values', 'children.values'])->find($optionId);

        if (!$parentOption || $parentOption->children->isEmpty()) {
            return [];
        }

        $options = [];

        foreach ($parentOption->values as $parentValue) {
            $parentValueName = $parentValue->translate('name');

            foreach ($parentOption->children as $child) {
                foreach ($child->values as $childValue) {
                    $key = $parentValue->id.'::'.$childValue->id;
                    $label = $parentValueName.' → '.$child->translate('name').': '.$childValue->translate('name');
                    $options[$key] = $label;
                }
            }
        }

        return $options;
    }

    private function addHierarchicalOption(ProductOption $parentOption, array $rawSelections): void
    {
        // Parse composite keys "parentValueId::childValueId" into parent_value_selections
        $parentValueSelections = [];

        foreach ($rawSelections as $compositeKey) {
            [$parentValueId, $childValueId] = explode('::', $compositeKey);

            $parentValue = $parentOption->values->find((int) $parentValueId);

            $childValue = null;
            foreach ($parentOption->children as $child) {
                $found = $child->values->find((int) $childValueId);
                if ($found) {
                    $childValue = $found;
                    break;
                }
            }

            if (!$parentValue || !$childValue) {
                continue;
            }

            $parentValueName = $parentValue->translate('name');
            $childValueName = $childValue->translate('name');

            if (!isset($parentValueSelections[$parentValueName])) {
                $parentValueSelections[$parentValueName] = [];
            }

            if (!in_array($childValueName, $parentValueSelections[$parentValueName])) {
                $parentValueSelections[$parentValueName][] = $childValueName;
            }
        }

        // Add parent entry
        $this->configuredOptions[] = array_merge(
            $this->mapOption(
                $parentOption,
                $parentOption->values->map(fn ($v) => $this->mapOptionValue($v, true))->toArray(),
            ),
            ['is_parent' => true],
        );

        // Add one child entry per child option
        foreach ($parentOption->children as $childOption) {
            // Build per-parent-value selections for this specific child
            $childSelections = [];
            foreach ($parentValueSelections as $parentValueName => $childValues) {
                // Filter to only values that belong to this child option
                $validChildValues = $childOption->values->map(fn ($v) => $v->translate('name'))->toArray();
                $childSelections[$parentValueName] = array_values(
                    array_intersect($childValues, $validChildValues),
                );
            }

            $this->configuredOptions[] = array_merge(
                $this->mapOption(
                    $childOption,
                    $childOption->values->map(fn ($v) => $this->mapOptionValue($v, true))->toArray(),
                ),
                [
                    'parent_key' => "option_{$parentOption->id}",
                    'parent_value_selections' => $childSelections,
                ],
            );
        }
    }

    private function combineWithIndependent(array $hierarchicalPermutations, array $independentOptions): array
    {
        if (empty($hierarchicalPermutations)) {
            // Only independent options — use Lunar's permutate
            $perms = Arr::permutate($independentOptions);

            if (count($independentOptions) === 1) {
                $key = array_key_first($independentOptions);

                return array_map(fn ($v) => [$key => $v], $perms);
            }

            return $perms;
        }

        // Cross-product: hierarchical × independent
        $indPerms = Arr::permutate($independentOptions);

        if (count($independentOptions) === 1) {
            $key = array_key_first($independentOptions);
            $indPerms = array_map(fn ($v) => [$key => $v], $indPerms);
        }

        $result = [];

        foreach ($hierarchicalPermutations as $hierPerm) {
            foreach ($indPerms as $indPerm) {
                $result[] = array_merge($hierPerm, $indPerm);
            }
        }

        return $result;
    }
}
