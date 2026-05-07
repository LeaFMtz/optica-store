<?php

declare(strict_types=1);

namespace App\Modifiers;

use Lunar\DataTypes\Price;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\TaxClass;

class ShippingModifier
{
    public function handle(Cart $cart, \Closure $next)
    {
        /**
         * Custom shipping option.
         * --------------------------------------------
         * If you do not wish to use the shipping add-on you can add
         * your own shipping options that will appear at checkout
         */
        if (config('shipping-tables.enabled') == false) {
            ShippingManifest::addOption(
                new ShippingOption(
                    name: 'Retiro en local',
                    description: 'Retiro en local',
                    identifier: 'RETLOC',
                    price: new Price(0, $cart->currency, 1),
                    taxClass: TaxClass::getDefault(),
                    collect: true,
                ),
            );
        }

        return $next($cart);
    }
}
