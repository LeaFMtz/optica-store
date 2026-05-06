<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LensUseResource\Pages;
use App\Filament\Resources\LensUseResource\RelationManagers\LensTypesRelationManager;
use App\Models\LensUse;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LensUseResource extends Resource
{
    protected static ?string $model = LensUse::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-eye';

    protected static string|UnitEnum|null $navigationGroup = 'Lentes';

    protected static ?string $navigationLabel = 'Usos de Lente';

    protected static ?string $label = 'Uso de Lente';

    protected static ?string $pluralLabel = 'Usos de Lente';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->maxLength(1000),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
                TextColumn::make('lensTypes_count')
                    ->label('Tipos')
                    ->counts('lensTypes'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelationManagers(): array
    {
        return [
            LensTypesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLensUses::route('/'),
            'create' => Pages\CreateLensUse::route('/create'),
            'edit' => Pages\EditLensUse::route('/{record}/edit'),
        ];
    }
}
