<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;

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
            'name' => 'Retail',
            'handle' => 'retail',
            'default' => true,
        ]);
    }
}
