<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Country;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxRate;
use Lunar\Models\TaxZone;
use Lunar\Models\TaxZoneCountry;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        if (TaxZone::count() > 0) {
            $this->command->info('Tax zones already exist, skipping...');

            return;
        }

        $taxClass = TaxClass::getDefault();

        $argentina = Country::firstWhere('iso3', 'ARG');

        $argentinaTaxZone = TaxZone::factory()->create([
            'name' => 'Argentina',
            'active' => true,
            'default' => true,
            'zone_type' => 'country',
        ]);

        TaxZoneCountry::factory()->create([
            'country_id' => $argentina->id,
            'tax_zone_id' => $argentinaTaxZone->id,
        ]);

        $ivaRate = TaxRate::factory()->create([
            'name' => 'IVA 21%',
            'tax_zone_id' => $argentinaTaxZone->id,
            'priority' => 1,
        ]);

        $ivaRate->taxRateAmounts()->createMany([
            [
                'percentage' => 21,
                'tax_class_id' => $taxClass->id,
            ],
        ]);
    }
}
