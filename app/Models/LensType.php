<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LensType extends Model
{
    protected $table = 'opt_lens_types';

    protected $fillable = [
        'lens_use_id',
        'name',
        'description',
        'sort_order',
    ];

    /**
     * @return BelongsTo<LensUse, LensType>
     */
    public function lensUse(): BelongsTo
    {
        return $this->belongsTo(LensUse::class);
    }

    /**
     * @return HasMany<ProductLensConfiguration>
     */
    public function productLensConfigurations(): HasMany
    {
        return $this->hasMany(ProductLensConfiguration::class);
    }
}
