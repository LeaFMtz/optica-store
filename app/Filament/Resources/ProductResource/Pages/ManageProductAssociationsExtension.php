<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResourceExtension;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Events\ProductAssociationsUpdated;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductAssociations as LunarManageProductAssociations;
use Lunar\Models\Product;

class ManageProductAssociationsExtension extends LunarManageProductAssociations
{
    protected static string $resource = ProductResourceExtension::class;

    protected static string $relationship = 'associations';

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::product-associations');
    }

    public function getTitle(): string
    {
        return __('lunarpanel::product.pages.associations.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('lunarpanel::product.pages.associations.label');
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table);

        $table->modifyQueryUsing(fn ($query) => $query->with('variants'));

        $columns = collect($table->getColumns())
            ->reject(fn ($column) => $column->getName() === 'target.variants.sku')
            ->push(
                TextColumn::make('selected_variant_skus')
                    ->label('SKU')
                    ->getStateUsing(function ($record) {
                        if (method_exists($record, 'variants')) {
                            return $record->variants
                                ->pluck('sku')
                                ->join(', ');
                        }

                        return '';
                    }),
            )
            ->toArray();

        return $table
            ->columns($columns)
            ->headerActions([
                $this->enhanceAssociationAction(CreateAction::make()),
            ])
            ->recordActions([
                $this->enhanceAssociationAction(EditAction::make()),
                DeleteAction::make()->after(
                    fn () => ProductAssociationsUpdated::dispatch($this->getOwnerRecord()),
                ),
            ]);
    }

    /**
     * Inyecta la lógica de variantes de forma segura
     */
    private function enhanceAssociationAction($action)
    {
        return $action
            ->schema(fn (): array => array_merge(
                // Recuperamos componentes originales de Lunar invocando el form del padre
                collect(parent::form(new Schema)->getComponents())->map(function ($component) {
                    if (method_exists($component, 'getName') && $component->getName() === 'product_target_id') {
                        return $component->live();
                    }

                    return $component;
                })->toArray(),
                [
                    CheckboxList::make('selected_variant_ids')
                        ->label('Seleccionar Variantes')
                        ->options(fn ($get) => $this->getVariantsForForm($get('product_target_id')))
                        ->visible(fn ($get) => filled($get('product_target_id')))
                        ->columns(2),
                ],
            ))
            // Usamos el helper when() para aplicar mutación solo si es EditAction
            ->when(
                $action instanceof EditAction,
                fn ($a) => $a->mutateRecordDataUsing(function (array $data, $record): array {
                    if (method_exists($record, 'variants')) {
                        $data['selected_variant_ids'] = $record->variants()->pluck('product_variant_id')->toArray();
                    }

                    return $data;
                }),
            )
            ->after(function ($record, array $data) {
                if (isset($data['selected_variant_ids']) && method_exists($record, 'variants')) {
                    $record->variants()->sync($data['selected_variant_ids']);
                }

                ProductAssociationsUpdated::dispatch($this->getOwnerRecord());
            });
    }

    /**
     * Obtiene las variantes del producto destino
     */
    private function getVariantsForForm(?int $targetId): array
    {
        if (!$targetId) {
            return [];
        }

        try {
            // Buscamos el producto sin scopes para evitar problemas de visibilidad en el panel
            $product = Product::withoutGlobalScopes()->find($targetId);

            if (!$product || $product->variants->isEmpty()) {
                return [];
            }

            return $product->variants->mapWithKeys(fn ($v) => [
                $v->getKey() => $v->sku.' | '.$v->translateAttribute('name'),
            ])->toArray();

        } catch (\Throwable $e) {
            return [];
        }
    }
}
