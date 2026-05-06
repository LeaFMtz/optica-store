<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LensUse extends Model
{
    protected $table = 'opt_lens_uses';

    protected $fillable = [
        'name',
        'description',
        'sort_order',
    ];

    /**
     * @return HasMany<LensType>
     */
    public function lensTypes(): HasMany
    {
        return $this->hasMany(LensType::class);
    }

    /**
     * @return HasMany<ProductLensConfiguration>
     */
    public function productLensConfigurations(): HasMany
    {
        return $this->hasMany(ProductLensConfiguration::class);
    }
}
