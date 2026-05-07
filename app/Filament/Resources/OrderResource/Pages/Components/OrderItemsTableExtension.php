<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Pages\Components;

use Lunar\Admin\Support\Extending\BaseExtension;

class OrderItemsTableExtension extends BaseExtension
{
    public function extendOrderLinesTableColumns(array $columns): array
    {
        return $columns;
    }
}
