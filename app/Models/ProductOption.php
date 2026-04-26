<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\ProductOption as LunarProductOption;

class ProductOption extends LunarProductOption
{
    /**
     * Get the parent option.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child options.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get all ancestor options (recursive).
     */
    public function ancestors(): Collection
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current !== null) {
            $ancestors[] = $current;
            $current = $current->parent;
        }

        return new Collection($ancestors);
    }

    /**
     * Check if this option is a descendant of another option.
     */
    public function isDescendantOf(int|string $optionId): bool
    {
        return $this->ancestors()->contains('id', $optionId);
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'parent_id' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (ProductOption $option) {
            $parentId = $option->parent_id ? (int) $option->parent_id : null;
            self::validateNoCycle($parentId, null);
        });

        static::updating(function (ProductOption $option) {
            $parentId = $option->parent_id ? (int) $option->parent_id : null;
            if ($parentId === $option->getKey()) {
                throw new \InvalidArgumentException('Una opción no puede ser padre de sí misma.');
            }

            self::validateNoCycle($parentId, $option->getKey());
        });
    }

    /**
     * Validate that setting parent_id would not create a cycle.
     */
    protected static function validateNoCycle(int|string|null $parentId, int|string|null $currentId): void
    {
        if ($parentId === null || $currentId === null) {
            return;
        }

        $parentId = (int) $parentId;
        $currentId = (int) $currentId;

        $parentOption = self::find($parentId);
        if ($parentOption !== null && $parentOption->isDescendantOf($currentId)) {
            throw new \InvalidArgumentException(
                'No se puede establecer esta relación: crearía un ciclo en la jerarquía.',
            );
        }
    }
}
