<?php

declare(strict_types=1);

namespace App\Filament\Extensions;

use App\Filament\Widgets\HierarchicalProductOptionsWidget;
use Lunar\Admin\Filament\Resources\ProductResource\Widgets\ProductOptionsWidget;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ManageProductVariantsPageExtension extends ResourceExtension
{
    public function headerWidgets(array $widgets): array
    {
        return array_map(
            fn ($widget) => $widget === ProductOptionsWidget::class
                ? HierarchicalProductOptionsWidget::class
                : $widget,
            $widgets,
        );
    }
}
