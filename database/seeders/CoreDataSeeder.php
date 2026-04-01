<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\AttributeGroup;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\ProductType;
use Lunar\Models\TaxClass;

class CoreDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Language::count() == 0) {
            Language::create([
                'code' => 'es',
                'name' => 'Español',
                'default' => true,
            ]);
            $this->command->info('Created default language: Español');
        }

        if (Currency::count() == 0) {
            Currency::create([
                'code' => 'ARS',
                'name' => 'Peso Argentino',
                'decimal_places' => 2,
                'exchange_rate' => 1,
                'default' => true,
            ]);
            $this->command->info('Created default currency: ARS');
        }

        if (CollectionGroup::count() == 0) {
            CollectionGroup::create([
                'name' => 'Tienda',
                'handle' => 'tienda',
            ]);
            $this->command->info('Created default collection group');
        }

        if (AttributeGroup::count() == 0) {
            AttributeGroup::factory()->createMany([
                ['name' => 'General', 'handle' => 'general'],
                ['name' => 'Especificaciones', 'handle' => 'especificaciones'],
            ]);
            $this->command->info('Created default attribute groups');
        }

        if (Country::count() == 0) {
            Country::factory()->createMany([
                ['iso3' => 'ARG', 'name' => 'Argentina', 'iso2' => 'AR', 'phonecode' => '54', 'currency' => 'ARS'],
                ['iso3' => 'USA', 'name' => 'United States', 'iso2' => 'US', 'phonecode' => '1', 'currency' => 'USD'],
                ['iso3' => 'GBR', 'name' => 'United Kingdom', 'iso2' => 'GB', 'phonecode' => '44', 'currency' => 'GBP'],
            ]);
            $this->command->info('Created default countries');
        }

        if (ProductType::count() == 0) {
            ProductType::create(['name' => 'Producto Estándar']);
            $this->command->info('Created default product type');
        }

        if (TaxClass::count() == 0) {
            TaxClass::create([
                'name' => 'IVA Estándar',
                'default' => true,
            ]);
            $this->command->info('Created default tax class');
        }

        $this->command->info('Core data seeded successfully!');
    }
}
