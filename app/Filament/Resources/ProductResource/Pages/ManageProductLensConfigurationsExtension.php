<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResourceExtension;
use App\Models\LensUse;
use App\Models\ProductLensConfiguration;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Pages\BaseManageRelatedRecords;
use Lunar\Models\Product;
use Lunar\Models\ProductType;

class ManageProductLensConfigurationsExtension extends BaseManageRelatedRecords
{
    protected static string $resource = ProductResourceExtension::class;

    protected static string $relationship = 'productLensConfigurations';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-eye-dropper';
    }

    public function getTitle(): string
    {
        return 'Configuración de Lentes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Configuración de Lentes';
    }

    private function crystalOptions(): array
    {
        $typeId = ProductType::where('name', 'Lentes Compuestos')->value('id');

        if (! $typeId) {
            return [];
        }

        return Product::where('status', 'published')
            ->where('product_type_id', $typeId)
            ->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->translateAttribute('name')])
            ->toArray();
    }

    /**
     * Reconstruct groups from existing configs for a given (product, use).
     * Types sharing the exact same set of crystals are merged into one group.
     *
     * @return array<array{type_ids: array<int>, crystal_ids: array<int>}>
     */
    private function existingGroups(int $productId, int $useId): array
    {
        $configs = ProductLensConfiguration::where([
            'product_id' => $productId,
            'lens_use_id' => $useId,
        ])->get();

        // Build per-type crystal sets
        $byType = $configs->groupBy('lens_type_id')->map(fn ($rows, $typeId) => [
            'type_id' => (int) $typeId,
            'crystal_ids' => $rows->pluck('crystal_product_id')->map(fn ($id) => (int) $id)->sort()->values()->toArray(),
        ]);

        // Group types that share the same crystal set
        return $byType
            ->groupBy(fn ($item) => implode(',', $item['crystal_ids']))
            ->map(fn ($items, $crystalKey) => [
                'type_ids' => $items->pluck('type_id')->values()->toArray(),
                'crystal_ids' => $items->first()['crystal_ids'],
            ])
            ->values()
            ->toArray();
    }

    /**
     * Replace all configurations for a (product, use) from a groups array.
     */
    private function replaceUseConfigs(int $productId, int $useId, array $groups): void
    {
        // Collect desired (type, crystal) pairs
        $desired = [];
        foreach ($groups as $group) {
            foreach ($group['type_ids'] ?? [] as $typeId) {
                foreach ($group['crystal_ids'] ?? [] as $crystalId) {
                    $desired[] = ['lens_type_id' => (int) $typeId, 'crystal_product_id' => (int) $crystalId];
                }
            }
        }

        $existing = ProductLensConfiguration::where([
            'product_id' => $productId,
            'lens_use_id' => $useId,
        ])->get();

        // Delete rows not in desired
        foreach ($existing as $row) {
            $keep = collect($desired)->contains(fn ($d) => $d['lens_type_id'] === (int) $row->lens_type_id
                && $d['crystal_product_id'] === (int) $row->crystal_product_id);

            if (! $keep) {
                $row->delete();
            }
        }

        // Create rows not yet existing
        $existingPairs = $existing->map(fn ($r) => [
            'lens_type_id' => (int) $r->lens_type_id,
            'crystal_product_id' => (int) $r->crystal_product_id,
        ]);

        foreach ($desired as $d) {
            $alreadyExists = $existingPairs->contains(fn ($e) => $e['lens_type_id'] === $d['lens_type_id']
                && $e['crystal_product_id'] === $d['crystal_product_id']);

            if (! $alreadyExists) {
                ProductLensConfiguration::create([
                    'product_id' => $productId,
                    'lens_use_id' => $useId,
                    'lens_type_id' => $d['lens_type_id'],
                    'crystal_product_id' => $d['crystal_product_id'],
                ]);
            }
        }
    }

    private function configFormSchema(bool $lockUse = false): array
    {
        $page = $this;

        return [
            Section::make('Uso')
                ->schema([
                    Select::make('_lens_use_id')
                        ->label('Uso de lente')
                        ->options(LensUse::orderBy('sort_order')->pluck('name', 'id'))
                        ->required()
                        ->live()
                        ->disabled($lockUse),
                ]),

            Section::make('Grupos de tipos y cristales')
                ->description('Cada grupo define qué tipos comparten los mismos cristales.')
                ->visible(fn (Get $get) => filled($get('_lens_use_id')))
                ->schema([
                    Repeater::make('groups')
                        ->label('')
                        ->schema([
                            Select::make('type_ids')
                                ->label('Tipos de lente')
                                ->options(fn (Get $get) => filled($get('../../_lens_use_id'))
                                    ? LensUse::find($get('../../_lens_use_id'))?->lensTypes()->orderBy('sort_order')->pluck('opt_lens_types.name', 'opt_lens_types.id') ?? []
                                    : [])
                                ->multiple()
                                ->required()
                                ->searchable()
                                ->disabled(fn (Get $get) => blank($get('../../_lens_use_id'))),

                            Select::make('crystal_ids')
                                ->label('Cristales (Lentes Compuestos)')
                                ->options($page->crystalOptions())
                                ->multiple()
                                ->required()
                                ->searchable(),
                        ])
                        ->columns(2)
                        ->addActionLabel('Agregar grupo')
                        ->defaultItems(1)
                        ->helperText('Ej: [Fotocromáticos + Polarizadas → Crystal X] y [Monofocal → Crystal Y]'),
                ]),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        $page = $this;

        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->whereIn('id', function ($sub) {
                    $sub->from('opt_product_lens_configurations')
                        ->selectRaw('MIN(id)')
                        ->groupBy('product_id', 'lens_use_id');
                })
            )
            ->defaultSort('lens_use_id')
            ->columns([
                TextColumn::make('lensUse.name')
                    ->label('Uso')
                    ->sortable(),

                TextColumn::make('tipos')
                    ->label('Tipos configurados')
                    ->getStateUsing(function ($record) {
                        return ProductLensConfiguration::where([
                            'product_id' => $record->product_id,
                            'lens_use_id' => $record->lens_use_id,
                        ])
                            ->with('lensType')
                            ->get()
                            ->pluck('lensType.name')
                            ->unique()
                            ->filter()
                            ->join(', ') ?: '—';
                    })
                    ->wrap(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar configuración')
                    ->schema(fn () => $page->configFormSchema(lockUse: false))
                    ->using(function (array $data) use ($page): ProductLensConfiguration {
                        $productId = $page->getOwnerRecord()->getKey();
                        $useId = (int) $data['_lens_use_id'];

                        $page->replaceUseConfigs($productId, $useId, $data['groups'] ?? []);

                        return ProductLensConfiguration::where([
                            'product_id' => $productId,
                            'lens_use_id' => $useId,
                        ])->first() ?? new ProductLensConfiguration();
                    }),
            ])
            ->actions([
                Action::make('edit_use')
                    ->label('Editar')
                    ->icon('heroicon-m-pencil-square')
                    ->fillForm(fn ($record) => [
                        '_lens_use_id' => $record->lens_use_id,
                        'groups' => $page->existingGroups($record->product_id, $record->lens_use_id),
                    ])
                    ->form(fn () => $page->configFormSchema(lockUse: true))
                    ->action(function (array $data, $record) use ($page): void {
                        $page->replaceUseConfigs(
                            $record->product_id,
                            $record->lens_use_id,
                            $data['groups'] ?? [],
                        );
                    }),

                Action::make('delete_use')
                    ->label('Eliminar')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Esto eliminará todas las configuraciones de cristales para este uso en este producto.')
                    ->action(function ($record): void {
                        ProductLensConfiguration::where([
                            'product_id' => $record->product_id,
                            'lens_use_id' => $record->lens_use_id,
                        ])->delete();
                    }),
            ]);
    }
}
