<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\ListProductsExtension;
use App\Filament\Resources\ProductResource\Pages\ManageProductAssociationsExtension;
use App\Filament\Resources\ProductResource\RelationManagers\ProductLensConfigurationRelationManager;
use Filament\Tables\Table;
use Lunar\Admin\Filament\Resources\ProductResource;

class ProductResourceExtension extends ProductResource
{
    public static function getDefaultRelations(): array
    {
        return array_merge(parent::getDefaultRelations(), [
            ProductLensConfigurationRelationManager::class,
        ]);
    }

    public static function getDefaultPages(): array
    {
        $pages = parent::getDefaultPages();

        $pages['index'] = ListProductsExtension::route('/');
        $pages['associations'] = ManageProductAssociationsExtension::route('/{record}/associations');

        return $pages;
    }

    public static function getDefaultTable(Table $table): Table
    {
        return parent::getDefaultTable($table);
    }

    protected static function getMainFormComponents(): array
    {
        return parent::getMainFormComponents();
    }
}
