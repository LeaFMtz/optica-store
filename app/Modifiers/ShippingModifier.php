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

        // ShippingManifest is in-memory per request. If the cart has an active Zipnova
        // option (ZN_*), re-register it from the session so the manifest is consistent
        // across all requests (shipping selection, cart calculation, order creation).
        $activeIdentifier = $cart->shippingAddress?->shipping_option;

        if ($activeIdentifier && str_starts_with($activeIdentifier, 'ZN_')) {
            $stored = session('zipnova_quote_options', [])[$activeIdentifier] ?? null;

            if ($stored) {
                ShippingManifest::addOption(new ShippingOption(
                    name: $stored['name'],
                    description: $stored['name'],
                    identifier: $activeIdentifier,
                    price: new Price((int) round($stored['price'] * 100), $cart->currency, 1),
                    taxClass: TaxClass::getDefault(),
                    collect: (($stored['service_type_code'] ?? '') === 'pickup_point'),
                ));
            }
        }

        return $next($cart);
    }
}
