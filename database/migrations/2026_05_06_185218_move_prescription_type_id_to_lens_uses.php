<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // This migration was created out-of-order (timestamp < 200001/200002).
        // On a fresh DB, prescription_types and the lens_types column don't exist yet —
        // 200002 handles the final state. Guards prevent double-execution.
        if (Schema::hasColumn($this->prefix.'lens_types', 'prescription_type_id')) {
            Schema::table($this->prefix.'lens_types', function (Blueprint $table) {
                $table->dropForeign(['prescription_type_id']);
                $table->dropColumn('prescription_type_id');
            });
        }

        if (Schema::hasTable($this->prefix.'prescription_types')
            && ! Schema::hasColumn($this->prefix.'lens_uses', 'prescription_type_id')) {
            Schema::table($this->prefix.'lens_uses', function (Blueprint $table) {
                $table->foreignId('prescription_type_id')
                    ->nullable()
                    ->after('sort_order')
                    ->constrained($this->prefix.'prescription_types')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table($this->prefix.'lens_uses', function (Blueprint $table) {
            $table->dropForeign(['prescription_type_id']);
            $table->dropColumn('prescription_type_id');
        });

        Schema::table($this->prefix.'lens_types', function (Blueprint $table) {
            $table->foreignId('prescription_type_id')
                ->nullable()
                ->after('lens_use_id')
                ->constrained($this->prefix.'prescription_types')
                ->nullOnDelete();
        });
    }
};
