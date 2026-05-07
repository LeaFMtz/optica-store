<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('lunar:import:address-data');

        $this->call([
            BasicConfig::class,
            ProductCatalogSeeder::class,
            LensConfigSeeder::class,
        ]);
    }
}
