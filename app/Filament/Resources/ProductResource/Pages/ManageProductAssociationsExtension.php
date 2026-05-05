<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResourceExtension;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentIcon;
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

        foreach ($table->getHeaderActions() as $action) {
            if ($action instanceof CreateAction) {
                $this->applyVariantLogic($action);
            }
        }

        // 2. Lógica para el botón "Editar" (Record Actions)
        foreach ($table->getActions() as $action) {
            if ($action instanceof EditAction) {
                $this->applyVariantLogic($action, isEdit: true);
            }
        }

        return $table;
    }

    /**
     * Función auxiliar para no repetir código de Schema y After
     */
    private function applyVariantLogic($action, bool $isEdit = false): void
    {
        $action->schema(fn (): array => array_merge(
            collect(parent::form(new Schema)->getComponents())->map(function ($component) {
                if (method_exists($component, 'getName') && $component->getName() === 'product_target_id') {
                    return $component->live();
                }

                return $component;
            })->toArray(),
            [
                Select::make('selected_variant_ids')
                    ->label('Seleccionar Variantes')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn ($get) => $this->getVariantsForForm($get('product_target_id')))
                    ->visible(fn ($get) => filled($get('product_target_id'))),
            ],
        ));

        // Solo si estamos editando, cargamos las variantes ya guardadas
        if ($isEdit) {
            $action->mutateRecordDataUsing(function (array $data, $record): array {
                $data['selected_variant_ids'] = $record->variants()->pluck('product_variant_id')->toArray();

                return $data;
            });
        }

        // Guardado (funciona para ambos)
        $action->after(function ($record, array $data) {
            if (isset($data['selected_variant_ids'])) {
                $record->variants()->sync($data['selected_variant_ids']);
            }

            ProductAssociationsUpdated::dispatch($this->getOwnerRecord());
        });
    }

    private function getVariantsForForm(?int $targetId): array
    {
        if (!$targetId) {
            return [];
        }

        try {
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
