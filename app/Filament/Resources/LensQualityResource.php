<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LensQualityResource\Pages;
use App\Models\LensQuality;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LensQualityResource extends Resource
{
    protected static ?string $model = LensQuality::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|UnitEnum|null $navigationGroup = 'Lentes';

    protected static ?string $navigationLabel = 'Calidades de Lente';

    protected static ?string $label = 'Calidad de Lente';

    protected static ?string $pluralLabel = 'Calidades de Lente';

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
                KeyValue::make('features')
                    ->label('Características')
                    ->keyLabel('Característica')
                    ->valueLabel('Valor')
                    ->addButtonLabel('Agregar característica')
                    ->reorderable(),
                TextInput::make('base_price')
                    ->label('Precio base (centavos)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_recommended')
                    ->label('Recomendado')
                    ->default(false),
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
                TextColumn::make('base_price')
                    ->label('Precio base')
                    ->sortable(),
                IconColumn::make('is_recommended')
                    ->label('Recomendado')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLensQualities::route('/'),
            'create' => Pages\CreateLensQuality::route('/create'),
            'edit' => Pages\EditLensQuality::route('/{record}/edit'),
        ];
    }
}
