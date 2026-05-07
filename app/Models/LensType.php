<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LensType extends Model
{
    protected $table = 'opt_lens_types';

    protected $fillable = [
        'name',
        'handle',
        'description',
        'sort_order',
    ];

    /**
     * @return BelongsToMany<LensUse>
     */
    public function lensUses(): BelongsToMany
    {
        return $this->belongsToMany(LensUse::class, 'opt_lens_type_lens_use');
    }

    /**
     * @return HasMany<ProductLensConfiguration>
     */
    public function productLensConfigurations(): HasMany
    {
        return $this->hasMany(ProductLensConfiguration::class);
    }
}
