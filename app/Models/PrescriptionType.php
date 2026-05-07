<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrescriptionType extends Model
{
    protected $table = 'opt_prescription_types';

    protected $fillable = [
        'name',
        'description',
        'requires_prescription',
    ];

    /**
     * @return HasMany<LensUse>
     */
    public function lensUses(): HasMany
    {
        return $this->hasMany(LensUse::class);
    }

    /**
     * @return BelongsToMany<PrescriptionField>
     */
    public function prescriptionFields(): BelongsToMany
    {
        return $this->belongsToMany(
            PrescriptionField::class,
            'opt_prescription_type_prescription_field',
            'prescription_type_id',
            'prescription_field_id',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    protected function casts(): array
    {
        return [
            'requires_prescription' => 'boolean',
        ];
    }
}
