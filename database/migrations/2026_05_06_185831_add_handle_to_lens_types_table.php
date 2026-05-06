<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'lens_types', function (Blueprint $table) {
            $table->string('handle')->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'lens_types', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn('handle');
        });
    }
};
