<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResourceExtension;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ListProducts;

class ListProductsExtension extends ListProducts
{
    protected static string $resource = ProductResourceExtension::class;

    public static function createActionFormInputs(): array
    {
        return parent::createActionFormInputs();
    }

    public static function createRecord(array $data, string $model): Model
    {
        return parent::createRecord($data, $model);
    }
}
