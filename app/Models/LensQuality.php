<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LensQuality extends Model
{
    protected $table = 'opt_lens_qualities';

    protected $fillable = [
        'name',
        'description',
        'features',
        'base_price',
        'sort_order',
        'is_recommended',
    ];

    /**
     * @return HasMany<ProductLensConfiguration>
     */
    public function productLensConfigurations(): HasMany
    {
        return $this->hasMany(ProductLensConfiguration::class);
    }

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_recommended' => 'boolean',
        ];
    }
}
