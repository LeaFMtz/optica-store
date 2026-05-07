<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\ListProductsExtension;
use App\Filament\Resources\ProductResource\Pages\ManageProductAssociationsExtension;
use App\Filament\Resources\ProductResource\Pages\ManageProductLensConfigurationsExtension;
use Filament\Tables\Table;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\EditProduct;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductAvailability;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductCollections;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductIdentifiers;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductInventory;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductMedia;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductPricing;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductShipping;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductUrls;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductVariants;

class ProductResourceExtension extends ProductResource
{
    public static function getDefaultPages(): array
    {
        $pages = parent::getDefaultPages();

        $pages['index'] = ListProductsExtension::route('/');
        $pages['associations'] = ManageProductAssociationsExtension::route('/{record}/associations');
        $pages['lens-configurations'] = ManageProductLensConfigurationsExtension::route('/{record}/lens-configurations');

        return $pages;
    }

    public static function getDefaultSubNavigation(): array
    {
        return [
            EditProduct::class,
            ManageProductAvailability::class,
            ManageProductMedia::class,
            ManageProductPricing::class,
            ManageProductIdentifiers::class,
            ManageProductInventory::class,
            ManageProductShipping::class,
            ManageProductVariants::class,
            ManageProductUrls::class,
            ManageProductCollections::class,
            ManageProductAssociationsExtension::class,
            ManageProductLensConfigurationsExtension::class,
        ];
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
