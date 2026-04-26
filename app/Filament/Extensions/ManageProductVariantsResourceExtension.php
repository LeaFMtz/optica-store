<?php

declare(strict_types=1);

namespace App\Filament\Extensions;

use App\Models\ProductOption;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Lunar\Admin\Support\Extending\ResourceExtension;
use Lunar\Models\ProductOptionValue;

class ManageProductVariantsResourceExtension extends ResourceExtension
{
    public function extendForm(Schema $form): Schema
    {
        // Esta extensión modifica el formulario que aparece al crear/editar una variante
        return $form->schema([
            ...$form->getComponents(withHidden: true),

            // Aquí inyectamos la lógica jerárquica
            Select::make('base_option_id')
                ->label('Tipo de Lente / Opción Raíz')
                ->options(ProductOption::whereNull('parent_id')->pluck('name', 'id'))
                ->live()
                ->afterStateUpdated(fn ($set) => $set('selected_hijos', [])),

            Repeater::make('hijos_config')
                ->label('Configuración de Sub-opciones')
                ->schema([
                    Select::make('child_id')
                        ->label('Sub-opción')
                        ->options(fn (Get $get) => ProductOption::where('parent_id', $get('../../base_option_id'))->pluck('name', 'id')
                        )
                        ->live(),
                    CheckboxList::make('values')
                        ->label('Valores a incluir')
                        ->options(fn (Get $get) => ProductOptionValue::where('product_option_id', $get('child_id'))->pluck('name', 'id')
                        )
                        ->columns(2),
                ])
                ->visible(fn (Get $get) => filled($get('base_option_id')))
                ->addable(true),
        ]);
    }
}
