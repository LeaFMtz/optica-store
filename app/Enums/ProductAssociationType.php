<?php

declare(strict_types=1);

namespace App\Enums;

use Lunar\Base\Enums\Concerns\ProvidesProductAssociationType;

enum ProductAssociationType: string implements ProvidesProductAssociationType
{
    public function label(): string
    {
        return match ($this) {
            self::CROSS_SELL => __('lunar::base.product-association-types.cross-sell'),
            self::UP_SELL => __('lunar::base.product-association-types.up-sell'),
            self::ALTERNATE => __('lunar::base.product-association-types.alternate'),
        };
    }
    case CROSS_SELL = 'cross-sell';
    case UP_SELL = 'up-sell';
    case ALTERNATE = 'alternate';
}
