<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\ManageProductAssociationsExtension;
use Lunar\Admin\Filament\Resources\ProductResource;

class ProductResourceExtension extends ProductResource
{
    public static function getDefaultPages(): array
    {
        $pages = parent::getDefaultPages();

        $pages['associations'] = ManageProductAssociationsExtension::route('/{record}/associations');

        return $pages;
    }
}
