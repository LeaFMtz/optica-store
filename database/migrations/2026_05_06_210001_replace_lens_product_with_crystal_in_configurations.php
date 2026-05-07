<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Free the product_id FK (it uses opt_plc_unique as its supporting index).
        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->dropForeign('opt_product_lens_configurations_product_id_foreign');
            $table->index('product_id', 'opt_plc_product_id_tmp');
        });

        // Step 2: Drop unique + lens_product FK + column.
        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->dropUnique('opt_plc_unique');
            $table->dropForeign(['lens_product_id']);
            $table->dropColumn('lens_product_id');
        });

        // Step 3: Add new columns + restore FKs + new unique constraint.
        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->foreignId('lens_use_id')
                ->after('product_id')
                ->constrained($this->prefix.'lens_uses');
            $table->foreignId('lens_type_id')
                ->after('lens_use_id')
                ->constrained($this->prefix.'lens_types');
            $table->foreignId('crystal_product_id')
                ->after('lens_type_id')
                ->constrained($this->prefix.'products')
                ->cascadeOnDelete();

            $table->dropIndex('opt_plc_product_id_tmp');

            $table->foreign('product_id', 'opt_product_lens_configurations_product_id_foreign')
                ->references('id')
                ->on($this->prefix.'products')
                ->cascadeOnDelete();

            $table->unique(
                ['product_id', 'lens_use_id', 'lens_type_id', 'crystal_product_id'],
                'opt_plc_unique',
            );
        });

        // Step 4: Drop opt_lens_products (no longer needed).
        Schema::dropIfExists($this->prefix.'lens_products');
    }

    public function down(): void
    {
        Schema::create($this->prefix.'lens_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained($this->prefix.'products')->nullOnDelete();
            $table->foreignId('lens_use_id')->constrained($this->prefix.'lens_uses')->cascadeOnDelete();
            $table->foreignId('lens_type_id')->constrained($this->prefix.'lens_types')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('base_price')->default(0);
            $table->json('features')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_recommended')->default(false);
            $table->timestamps();
        });

        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->dropForeign('opt_product_lens_configurations_product_id_foreign');
            $table->index('product_id', 'opt_plc_product_id_tmp');
        });

        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->dropUnique('opt_plc_unique');
            $table->dropForeign(['crystal_product_id']);
            $table->dropForeign(['lens_type_id']);
            $table->dropForeign(['lens_use_id']);
            $table->dropColumn(['crystal_product_id', 'lens_type_id', 'lens_use_id']);

            $table->foreignId('lens_product_id')
                ->after('product_id')
                ->constrained($this->prefix.'lens_products')
                ->cascadeOnDelete();

            $table->dropIndex('opt_plc_product_id_tmp');

            $table->foreign('product_id', 'opt_product_lens_configurations_product_id_foreign')
                ->references('id')->on($this->prefix.'products')
                ->cascadeOnDelete();

            $table->unique(['product_id', 'lens_product_id'], 'opt_plc_unique');
        });
    }
};
