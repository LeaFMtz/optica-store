<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\LensQuality;
use App\Models\LensType;
use App\Models\LensUse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
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
                Select::make('lens_use_id')
                    ->label('Uso')
                    ->options(LensUse::orderBy('sort_order')->pluck('name', 'id'))
                    ->required()
                    ->live(),

                Select::make('lens_type_id')
                    ->label('Tipo de lente')
                    ->options(fn (Get $get) => LensType::where('lens_use_id', $get('lens_use_id'))->orderBy('sort_order')->pluck('name', 'id'))
                    ->required()
                    ->disabled(fn (Get $get) => blank($get('lens_use_id'))),

                Select::make('lens_quality_id')
                    ->label('Calidad')
                    ->options(LensQuality::orderBy('sort_order')->pluck('name', 'id'))
                    ->required(),

                TextInput::make('price_override')
                    ->label('Precio override (centavos, dejar vacío para usar base)')
                    ->numeric()
                    ->nullable()
                    ->placeholder('Usar precio base de la calidad'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lensUse.name')
                    ->label('Uso')
                    ->sortable(),

                TextColumn::make('lensType.name')
                    ->label('Tipo')
                    ->sortable(),

                TextColumn::make('lensQuality.name')
                    ->label('Calidad')
                    ->sortable(),

                TextColumn::make('price_override')
                    ->label('Override')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? (string) $state : 'base')
                    ->sortable(),

                TextColumn::make('final_price')
                    ->label('Precio final')
                    ->getStateUsing(fn ($record): int => $record->finalPrice()),
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
