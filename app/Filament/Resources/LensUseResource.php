<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LensUseResource\Pages;
use App\Models\LensType;
use App\Models\LensUse;
use App\Models\PrescriptionType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
                Section::make('Información general')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Tipos de Lente')
                    ->description('Asigná qué tipos de lente aplican a este uso.')
                    ->schema([
                        Select::make('lensTypes')
                            ->label('Tipos asignados')
                            ->multiple()
                            ->relationship('lensTypes', 'name')
                            ->getOptionLabelFromRecordUsing(fn (LensType $record) => "{$record->name} — {$record->handle}")
                            ->preload()
                            ->searchable()
                            ->helperText('Buscá por nombre o handle.'),
                    ]),

                Section::make('Receta')
                    ->description('Configuración de receta médica para este uso de lente.')
                    ->schema([
                        Select::make('prescription_type_id')
                            ->label('Tipo de receta')
                            ->options(PrescriptionType::orderBy('name')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->placeholder('Sin receta')
                            ->helperText('Si este uso requiere receta médica, seleccioná el tipo correspondiente.'),
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
                TextColumn::make('prescriptionType.name')
                    ->label('Tipo de receta')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('lensTypes_count')
                    ->label('Tipos')
                    ->counts('lensTypes'),
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
            'index' => Pages\ListLensUses::route('/'),
            'create' => Pages\CreateLensUse::route('/create'),
            'edit' => Pages\EditLensUse::route('/{record}/edit'),
        ];
    }
}
