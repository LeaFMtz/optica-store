<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Banner;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Banners';

    protected static ?string $label = 'Banner';

    protected static ?string $pluralLabel = 'Banners';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),
                Fieldset::make('Información básica')
                    ->schema([
                        TextInput::make('url')
                            ->label('URL de destino')
                            ->url()
                            ->maxLength(500),
                    ]),
                Fieldset::make('Imágenes')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label('Imagen (escritorio)')
                            ->collection('image')
                            ->required()
                            ->image()
                            ->panelLayout('compact'),
                        SpatieMediaLibraryFileUpload::make('mobile_image')
                            ->label('Imagen móvil')
                            ->collection('mobile_image')
                            ->image()
                            ->helperText('La imagen mobile es opcional. Si no se carga, se usará la imagen desktop como fallback.')
                            ->panelLayout('compact'),
                    ]),
                Fieldset::make('Posición')
                    ->schema([
                        Select::make('position')
                            ->label('Posición')
                            ->required()
                            ->options(Banner::positions()),
                        TextInput::make('order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('image')
                    ->label('Imagen')
                    ->square(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Posición')
                    ->formatStateUsing(fn (string $state): string => Banner::positions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('order')
                    ->label('Orden')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Activo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->label('Posición')
                    ->options(Banner::positions()),
                Tables\Filters\Filter::make('is_active')
                    ->label('Solo activos')
                    ->query(fn ($query) => $query->where('is_active', true)),
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
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => BannerResource\Pages\ListBanners::route('/'),
            'create' => BannerResource\Pages\CreateBanner::route('/create'),
            'edit' => BannerResource\Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
