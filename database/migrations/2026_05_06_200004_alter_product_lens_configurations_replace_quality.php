<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop the product_id FK (it uses opt_plc_unique as its supporting index)
        // and add a standalone index on product_id so we can then safely drop opt_plc_unique.
        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->dropForeign('opt_product_lens_configurations_product_id_foreign');
            $table->index('product_id', 'opt_plc_product_id_tmp');
        });

        // Step 2: Drop the unique index and the columns no longer needed.
        // dropForeign removes the FK constraint AND its backing index — required on MySQL
        // before dropping the index or column directly.
        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->dropUnique('opt_plc_unique');
            $table->dropForeign(['lens_use_id']);
            $table->dropForeign(['lens_type_id']);
            $table->dropForeign(['lens_quality_id']);
            $table->dropColumn(['lens_use_id', 'lens_type_id', 'lens_quality_id']);
        });

        // Step 3: Add the new FK column, restore product_id FK, and add the new unique constraint.
        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->foreignId('lens_product_id')
                ->after('product_id')
                ->constrained($this->prefix.'lens_products')
                ->cascadeOnDelete();

            $table->dropIndex('opt_plc_product_id_tmp');

            $table->foreign('product_id', 'opt_product_lens_configurations_product_id_foreign')
                ->references('id')
                ->on($this->prefix.'products')
                ->cascadeOnDelete();

            $table->unique(['product_id', 'lens_product_id'], 'opt_plc_unique');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->dropForeign('opt_product_lens_configurations_product_id_foreign');
            $table->index('product_id', 'opt_plc_product_id_tmp');
        });

        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->dropUnique('opt_plc_unique');
            $table->dropForeign(['lens_product_id']);
            $table->dropColumn('lens_product_id');
        });

        Schema::table($this->prefix.'product_lens_configurations', function (Blueprint $table) {
            $table->foreignId('lens_use_id')
                ->constrained($this->prefix.'lens_uses');
            $table->foreignId('lens_type_id')
                ->constrained($this->prefix.'lens_types');
            $table->foreignId('lens_quality_id')
                ->constrained($this->prefix.'lens_qualities');

            $table->dropIndex('opt_plc_product_id_tmp');

            $table->foreign('product_id', 'opt_product_lens_configurations_product_id_foreign')
                ->references('id')
                ->on($this->prefix.'products')
                ->cascadeOnDelete();

            $table->unique(
                ['product_id', 'lens_use_id', 'lens_type_id', 'lens_quality_id'],
                'opt_plc_unique',
            );
        });
    }
};
