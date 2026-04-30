<?php

declare(strict_types=1);

namespace App\Filament\Extensions;

use App\Models\ProductOption;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ProductOptionResourceExtension extends ResourceExtension
{
    public function extendForm(Schema $form): Schema
    {
        $options = ProductOption::query()
            ->select('id', 'name', 'handle')
            ->get()
            ->mapWithKeys(function ($opt) {
                // Extraemos el nombre del objeto ArrayObject.
                // Si 'name' es traducible, buscamos el valor del locale actual.
                $label = is_object($opt->name) || is_array($opt->name)
                    ? ($opt->translate('name') ?: $opt->handle)
                    : ($opt->name ?: $opt->handle);

                return [(int) $opt->id => $label];
            })
            ->all();

        return $form->schema([
            ...$form->getComponents(withHidden: true),
            Select::make('parent_id')
                ->label('Opción Padre')
                ->nullable()
                ->options($options)
                ->searchable() // Recomendado si la lista crece
                ->placeholder('Sin padre (opción raíz)'),
        ]);
    }

    public function extendTable(Table $table): Table
    {
        return $table->columns([
            ...$table->getColumns(),
            TextColumn::make('parent.name')
                ->label('Padre')
                ->placeholder('—')
                ->formatStateUsing(function ($state, ProductOption $record) {
                    return $record->parent?->translate('name') ?: $record->parent?->handle;
                }),
        ]);
    }
}
