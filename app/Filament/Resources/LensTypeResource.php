<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LensTypeResource\Pages;
use App\Models\LensType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LensTypeResource extends Resource
{
    protected static ?string $model = LensType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Lentes';

    protected static ?string $navigationLabel = 'Tipos de Lente';

    protected static ?string $label = 'Tipo de Lens';

    protected static ?string $pluralLabel = 'Tipos de Lente';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('handle', str($state)->slug())),

                        TextInput::make('handle')
                            ->label('Handle')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('monofocal-distancia')
                            ->helperText('Identificador único. Se genera automáticamente desde el nombre.'),
                    ]),

                Section::make('Detalles')
                    ->columns(3)
                    ->schema([
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpan(2),

                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
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

                TextColumn::make('handle')
                    ->label('Handle')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Handle copiado'),

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
            'index' => Pages\ListLensTypes::route('/'),
            'create' => Pages\CreateLensType::route('/create'),
            'edit' => Pages\EditLensType::route('/{record}/edit'),
        ];
    }
}
