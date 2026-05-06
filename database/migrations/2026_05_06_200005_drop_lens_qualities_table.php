<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists($this->prefix.'lens_qualities');
    }

    public function down(): void
    {
        Schema::create($this->prefix.'lens_qualities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('base_price');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_recommended')->default(false);
            $table->timestamps();
        });
    }
};
