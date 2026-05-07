<?php

declare(strict_types=1);

namespace App\Search;

use Illuminate\Database\Eloquent\Model;
use Lunar\Search\ProductIndexer as LunarProductIndexer;

class ProductIndexer extends LunarProductIndexer
{
    /**
     * {@inheritDoc}
     */
    public function toSearchableArray(Model $model): array
    {
        return parent::toSearchableArray($model);
    }

    /**
     * {@inheritDoc}
     *
     * @return list<string>
     */
    public function getFilterableFields(): array
    {
        return parent::getFilterableFields();
    }
}
