<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration as LunarMigration;

return new class extends LunarMigration
{
    public function up(): void
    {
        Schema::dropIfExists($this->prefix.'product_association_variants');
    }

    public function down(): void
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
};
