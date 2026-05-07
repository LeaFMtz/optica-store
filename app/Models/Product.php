<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Product as LunarProduct;

class Product extends LunarProduct
{
    protected $fillable = [
        'attribute_data',
        'product_type_id',
        'status',
        'brand_id',
    ];

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeBrowsable(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * @return HasMany<ProductLensConfiguration>
     */
    public function productLensConfigurations(): HasMany
    {
        return $this->hasMany(ProductLensConfiguration::class);
    }

    protected function casts(): array
    {
        return parent::casts();
    }
}
