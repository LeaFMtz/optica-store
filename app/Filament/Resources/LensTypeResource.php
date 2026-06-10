<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LensTypeResource\Pages;
use App\Models\LensType;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
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
                Action::make('delete')
                    ->label(__('Borrar'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->modalHeading('Eliminar tipo de lente')
                    ->modalDescription(function (LensType $record): string {
                        $count = $record->productLensConfigurations()->count();

                        if ($count > 0) {
                            return "Este tipo de lente tiene {$count} configuración(es) de producto asociada(s) "
                                .'que serán desvinculadas antes de eliminarlo. No se eliminarán las configuraciones.';
                        }

                        return 'Este tipo de lente no tiene configuraciones asociadas. Se eliminará permanentemente.';
                    })
                    ->modalSubmitActionLabel(function (LensType $record): string {
                        return $record->productLensConfigurations()->count() > 0
                            ? 'Si, desvincular y eliminar'
                            : 'Si, eliminar';
                    })
                    ->action(function (LensType $record): void {
                        $record->productLensConfigurations()->update(['lens_type_id' => null]);
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading('Eliminar tipos de lente')
                        ->modalDescription('Si los tipos de lente seleccionados tienen configuraciones de producto asociadas, serán desvinculadas automáticamente antes de la eliminación.')
                        ->modalSubmitActionLabel('Si, desvincular y eliminar')
                        ->before(function (Collection $records): void {
                            $records->each(
                                fn (LensType $record) => $record->productLensConfigurations()->update(['lens_type_id' => null]),
                            );
                        }),
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
