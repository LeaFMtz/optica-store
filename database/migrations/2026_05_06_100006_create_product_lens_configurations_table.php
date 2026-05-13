<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->cascadeOnDelete();
            $table->foreignId('lens_use_id')
                ->nullable()
                ->constrained($this->prefix.'lens_uses');
            $table->foreignId('lens_type_id')
                ->nullable()
                ->constrained($this->prefix.'lens_types');
            $table->foreignId('crystal_product_id')
                ->nullable()
                ->constrained($this->prefix.'products')
                ->cascadeOnDelete();
            $table->unsignedInteger('price_override')->nullable();
            $table->timestamps();

            $table->unique(
                ['product_id', 'lens_use_id', 'lens_type_id', 'crystal_product_id'],
                'opt_plc_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_lens_configurations');
    }
};
