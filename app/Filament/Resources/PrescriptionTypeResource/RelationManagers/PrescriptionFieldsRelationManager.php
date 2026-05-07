<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionTypeResource\RelationManagers;

use App\Models\PrescriptionField;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrescriptionFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'prescriptionFields';

    protected static ?string $title = 'Campos de receta';

    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Clave')
                    ->copyable()
                    ->copyMessage('Clave copiada'),

                TextColumn::make('label')
                    ->label('Etiqueta'),

                TextColumn::make('min')
                    ->label('Mín.'),

                TextColumn::make('max')
                    ->label('Máx.'),

                TextColumn::make('step')
                    ->label('Paso'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Agregar campo')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query->orderBy('sort_order'))
                    ->recordTitle(fn (PrescriptionField $record) => "{$record->label} ({$record->key})")
                    ->recordSelectSearchColumns(['key', 'label']),
            ])
            ->actions([
                DetachAction::make()->label('Quitar'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
