<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'product_association_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_association_id')
                ->constrained($this->prefix.'product_associations')
                ->cascadeOnDelete();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->cascadeOnDelete();
            $table->unique(['product_association_id', 'product_variant_id'], 'opt_assoc_var_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_association_variants');
    }
};