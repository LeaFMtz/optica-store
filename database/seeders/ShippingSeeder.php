<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Price;
use Lunar\Shipping\Models\ShippingMethod;
use Lunar\Shipping\Models\ShippingRate;
use Lunar\Shipping\Models\ShippingZone;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::getDefault();

        $argentina = Country::where('iso3', '=', 'ARG')->first();
        $argentinaId = $argentina?->id ?? 235;

        $andreaniMethod = ShippingMethod::create([
            'name' => 'Andreani Envío a Domicilio',
            'code' => 'ANDREANI_DOMICILIO',
            'enabled' => true,
            'driver' => 'ship-by',
            'data' => [
                'charge_by' => 'weight',
            ],
        ]);

        $andreaniZone = ShippingZone::create([
            'name' => 'Argentina',
            'type' => 'countries',
        ]);

        $andreaniZone->countries()->sync([$argentinaId]);

        $andreaniRate = ShippingRate::create([
            'shipping_zone_id' => $andreaniZone->id,
            'shipping_method_id' => $andreaniMethod->id,
            'enabled' => true,
        ]);

        Price::create([
            'priceable_type' => (new ShippingRate)->getMorphClass(),
            'priceable_id' => $andreaniRate->id,
            'price' => 2500,
            'min_quantity' => 1,
            'currency_id' => $currency->id,
        ]);

        Price::create([
            'priceable_type' => (new ShippingRate)->getMorphClass(),
            'priceable_id' => $andreaniRate->id,
            'price' => 1800,
            'min_quantity' => 5000,
            'currency_id' => $currency->id,
        ]);

        $andreaniPickup = ShippingMethod::create([
            'name' => 'Andreani Pickup (Sucursal)',
            'code' => 'ANDREANI_PICKUP',
            'enabled' => true,
            'driver' => 'ship-by',
            'data' => [
                'charge_by' => 'weight',
            ],
        ]);

        $pickupRate = ShippingRate::create([
            'shipping_zone_id' => $andreaniZone->id,
            'shipping_method_id' => $andreaniPickup->id,
            'enabled' => true,
        ]);

        Price::create([
            'priceable_type' => (new ShippingRate)->getMorphClass(),
            'priceable_id' => $pickupRate->id,
            'price' => 1500,
            'min_quantity' => 1,
            'currency_id' => $currency->id,
        ]);

        $localPickup = ShippingMethod::create([
            'name' => 'Retiro en Local',
            'code' => 'RETIRO_LOCAL',
            'enabled' => true,
            'driver' => 'pickup',
            'data' => [
                'collection' => true,
            ],
        ]);

        $localZone = ShippingZone::create([
            'name' => 'Local Pickup',
            'type' => 'countries',
        ]);

        $localZone->countries()->sync([$argentinaId]);

        $localRate = ShippingRate::create([
            'shipping_zone_id' => $localZone->id,
            'shipping_method_id' => $localPickup->id,
            'enabled' => true,
        ]);

        Price::create([
            'priceable_type' => (new ShippingRate)->getMorphClass(),
            'priceable_id' => $localRate->id,
            'price' => 0,
            'min_quantity' => 1,
            'currency_id' => $currency->id,
        ]);
    }
}
