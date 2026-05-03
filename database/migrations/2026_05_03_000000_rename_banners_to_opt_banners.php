<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('banners', 'opt_banners');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('opt_banners', 'banners');
    }
};
