<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table
        Schema::create($this->prefix.'lens_type_lens_use', function (Blueprint $table) {
            $table->unsignedBigInteger('lens_type_id');
            $table->unsignedBigInteger('lens_use_id');

            $table->foreign('lens_type_id', 'opt_lt_lu_type_fk')
                ->references('id')->on($this->prefix.'lens_types')
                ->cascadeOnDelete();
            $table->foreign('lens_use_id', 'opt_lt_lu_use_fk')
                ->references('id')->on($this->prefix.'lens_uses')
                ->cascadeOnDelete();

            $table->primary(['lens_type_id', 'lens_use_id']);
        });

        // 2. Migrate existing lens_use_id data into the pivot
        DB::table($this->prefix.'lens_types')
            ->whereNotNull('lens_use_id')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table($this->prefix.'lens_type_lens_use')->insertOrIgnore([
                    'lens_type_id' => $row->id,
                    'lens_use_id' => $row->lens_use_id,
                ]);
            });

        // 3. Drop the lens_use_id FK and column from lens_types
        Schema::table($this->prefix.'lens_types', function (Blueprint $table) {
            $table->dropForeign('opt_lens_types_lens_use_id_foreign');
            $table->dropColumn('lens_use_id');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'lens_types', function (Blueprint $table) {
            $table->foreignId('lens_use_id')
                ->nullable()
                ->constrained($this->prefix.'lens_uses')
                ->cascadeOnDelete();
        });

        // Restore lens_use_id from pivot (take first use if multiple)
        DB::table($this->prefix.'lens_type_lens_use')
            ->get()
            ->each(function ($row) {
                DB::table($this->prefix.'lens_types')
                    ->where('id', $row->lens_type_id)
                    ->whereNull('lens_use_id')
                    ->update(['lens_use_id' => $row->lens_use_id]);
            });

        Schema::dropIfExists($this->prefix.'lens_type_lens_use');
    }
};
