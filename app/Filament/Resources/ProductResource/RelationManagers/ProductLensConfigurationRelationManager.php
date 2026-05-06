<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\LensProduct;
use App\Models\LensType;
use App\Models\LensUse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductLensConfigurationRelationManager extends RelationManager
{
    protected static string $relationship = 'productLensConfigurations';

    protected static ?string $title = 'Configuraciones de Lente';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Selección de lente')
                    ->description('Elegí el uso, tipo y cristal disponible para este marco.')
                    ->schema([
                        Select::make('_lens_use_id')
                            ->label('Uso')
                            ->options(LensUse::orderBy('sort_order')->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($state, $record, $set) {
                                if ($record?->lensProduct) {
                                    $set('_lens_use_id', $record->lensProduct->lens_use_id);
                                }
                            }),

                        Select::make('_lens_type_id')
                            ->label('Tipo de lente')
                            ->options(fn (Get $get) => LensType::where('lens_use_id', $get('_lens_use_id'))
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->disabled(fn (Get $get) => blank($get('_lens_use_id')))
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($state, $record, $set) {
                                if ($record?->lensProduct) {
                                    $set('_lens_type_id', $record->lensProduct->lens_type_id);
                                }
                            }),

                        Select::make('lens_product_id')
                            ->label('Cristal')
                            ->options(fn (Get $get) => LensProduct::where('lens_use_id', $get('_lens_use_id'))
                                ->where('lens_type_id', $get('_lens_type_id'))
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->required()
                            ->disabled(fn (Get $get) => blank($get('_lens_type_id')))
                            ->searchable(),
                    ]),

                Section::make('Precio')
                    ->schema([
                        TextInput::make('price_override')
                            ->label('Precio override (centavos)')
                            ->numeric()
                            ->nullable()
                            ->placeholder('Dejar vacío para usar el precio base del cristal')
                            ->helperText('Si se completa, este valor reemplaza el precio base del cristal.'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lensProduct.lensUse.name')
                    ->label('Uso')
                    ->sortable(),

                TextColumn::make('lensProduct.lensType.name')
                    ->label('Tipo')
                    ->sortable(),

                TextColumn::make('lensProduct.name')
                    ->label('Cristal')
                    ->sortable(),

                TextColumn::make('price_override')
                    ->label('Override')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? '$'.number_format($state / 100, 2) : '—')
                    ->sortable(),

                TextColumn::make('final_price')
                    ->label('Precio final')
                    ->getStateUsing(fn ($record): string => '$'.number_format($record->finalPrice() / 100, 2)),
            ])
            ->headerActions([
                CreateAction::make(),
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
}
