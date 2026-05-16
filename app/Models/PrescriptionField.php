<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PrescriptionField extends Model
{
    protected $table = 'opt_prescription_fields';

    protected $fillable = [
        'key',
        'label',
        'min',
        'max',
        'step',
        'sort_order',
    ];

    /**
     * @return BelongsToMany<PrescriptionType>
     */
    public function prescriptionTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            PrescriptionType::class,
            'opt_prescription_type_prescription_field',
            'prescription_field_id',
            'prescription_type_id',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    protected function casts(): array
    {
        return [
            'min' => 'float',
            'max' => 'float',
            'step' => 'float',
        ];
    }
}
