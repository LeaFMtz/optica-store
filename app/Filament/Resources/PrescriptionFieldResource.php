<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PrescriptionFieldResource\Pages;
use App\Models\PrescriptionField;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PrescriptionFieldResource extends Resource
{
    protected static ?string $model = PrescriptionField::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-variable';

    protected static string|UnitEnum|null $navigationGroup = 'Lentes';

    protected static ?string $navigationLabel = 'Campos';

    protected static ?string $navigationParentItem = 'Recetas';

    protected static ?string $label = 'Campo';

    protected static ?string $pluralLabel = 'Campos';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('key')
                            ->label('Clave interna')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('sphere')
                            ->helperText('Identificador único, en minúsculas y sin espacios. No puede cambiarse una vez en uso.'),

                        TextInput::make('label')
                            ->label('Etiqueta visible')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Esfera'),
                    ]),

                Section::make('Rango de valores')
                    ->columns(3)
                    ->schema([
                        TextInput::make('min')
                            ->label('Mínimo')
                            ->numeric()
                            ->required(),

                        TextInput::make('max')
                            ->label('Máximo')
                            ->numeric()
                            ->required(),

                        TextInput::make('step')
                            ->label('Paso')
                            ->numeric()
                            ->required()
                            ->default(0.25)
                            ->placeholder('0.25'),

                        TextInput::make('sort_order')
                            ->label('Orden por defecto')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Clave')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Clave copiada'),

                TextColumn::make('label')
                    ->label('Etiqueta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('min')
                    ->label('Mín.')
                    ->sortable(),

                TextColumn::make('max')
                    ->label('Máx.')
                    ->sortable(),

                TextColumn::make('step')
                    ->label('Paso')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrescriptionFields::route('/'),
            'create' => Pages\CreatePrescriptionField::route('/create'),
            'edit' => Pages\EditPrescriptionField::route('/{record}/edit'),
        ];
    }
}
