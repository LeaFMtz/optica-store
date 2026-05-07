<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LensUse extends Model
{
    protected $table = 'opt_lens_uses';

    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'prescription_type_id',
    ];

    /**
     * @return BelongsTo<PrescriptionType, LensUse>
     */
    public function prescriptionType(): BelongsTo
    {
        return $this->belongsTo(PrescriptionType::class);
    }

    /**
     * @return BelongsToMany<LensType>
     */
    public function lensTypes(): BelongsToMany
    {
        return $this->belongsToMany(LensType::class, 'opt_lens_type_lens_use');
    }

    /**
     * @return HasMany<ProductLensConfiguration>
     */
    public function productLensConfigurations(): HasMany
    {
        return $this->hasMany(ProductLensConfiguration::class);
    }
}
