<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PrescriptionTypeResource\Pages;
use App\Models\PrescriptionType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PrescriptionTypeResource extends Resource
{
    protected static ?string $model = PrescriptionType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Lentes';

    protected static ?string $navigationLabel = 'Recetas';

    protected static ?string $label = 'Receta';

    protected static ?string $pluralLabel = 'Recetas';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Información general')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(1000),

                        Select::make('prescriptionFields')
                            ->label('Campos de receta')
                            ->multiple()
                            ->relationship('prescriptionFields', 'label')
                            ->preload()
                            ->helperText('Seleccioná los campos que incluye esta receta (esfera, cilindro, eje, etc.).'),
                    ]),

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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrescriptionTypes::route('/'),
            'create' => Pages\CreatePrescriptionType::route('/create'),
            'edit' => Pages\EditPrescriptionType::route('/{record}/edit'),
        ];
    }
}
