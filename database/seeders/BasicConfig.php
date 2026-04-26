<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxZone;

class BasicConfig extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Currency::create([
            'name' => 'Pesos',
            'code' => 'ARS',
            'exchange_rate' => 1,
            'decimal_places' => 2,
            'enabled' => true,
            'default' => true,
            'sync_prices' => true,
        ]);

        Language::create([
            'code' => 'es',
            'name' => 'Español',
            'default' => true,
        ]);

        Channel::create([
            'name' => 'web',
            'handle' => 'web',
            'default' => true,
        ]);

        $taxClassIva21 = TaxClass::create([
            'name' => 'IVA 21%',
            'default' => true,
        ]);

        $taxClassIva10 = TaxClass::create([
            'name' => 'IVA 10.5%',
        ]);

        $taxZone = TaxZone::create([
            'name' => 'Argentina',
            'zone_type' => 'country',
            'price_display' => true,
            'active' => true,
            'default' => true,
        ]);

        $taxZone->countries()->create([
            'country_id' => 11, // ARGENTINA SEGUN EL IMPORT DEFAULT DE LUNAR
        ]);

        $taxRates = $taxZone->taxRates()->create([
            'priority' => 1,
            'name' => 'IVA',
        ]);

        $taxRates->taxRateAmounts()->create([
            'percentage' => 21,
            'tax_class_id' => $taxClassIva21->id,
        ]);

        $taxRates->taxRateAmounts()->create([
            'percentage' => 10.5,
            'tax_class_id' => $taxClassIva10->id,
        ]);
    }
}
