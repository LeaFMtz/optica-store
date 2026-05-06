<?php

declare(strict_types=1);

namespace App\Filament\Resources\LensUseResource\RelationManagers;

use App\Models\LensType;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LensTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'lensTypes';

    protected static ?string $title = 'Tipos de Lente';

    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('handle')
                    ->label('Handle')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Handle copiado'),

                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Asignar tipo de lente')
                    ->preloadRecordSelect()
                    ->recordTitle(fn (LensType $record) => "{$record->name} ({$record->handle})")
                    ->recordSelectSearchColumns(['name', 'handle']),
            ])
            ->actions([
                DetachAction::make()->label('Quitar'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
