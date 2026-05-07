<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'lens_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->nullable()
                ->constrained($this->prefix.'products')
                ->nullOnDelete();
            $table->foreignId('lens_use_id')
                ->constrained($this->prefix.'lens_uses')
                ->cascadeOnDelete();
            $table->foreignId('lens_type_id')
                ->constrained($this->prefix.'lens_types')
                ->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('base_price')->default(0);
            $table->json('features')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_recommended')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'lens_products');
    }
};
