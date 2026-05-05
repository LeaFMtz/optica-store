<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Lunar\Models\ProductAssociation as LunarProductAssociation;
use Lunar\Models\ProductVariant;

class ProductAssociationVariant extends LunarProductAssociation
{
    public function variants(): BelongsToMany
    {
        $prefix = config('lunar.database.table_prefix', 'opt_');

        return $this->belongsToMany(
            ProductVariant::class,
            "{$prefix}product_association_variants",
            'product_association_id',
            'product_variant_id',
        );
    }
}

