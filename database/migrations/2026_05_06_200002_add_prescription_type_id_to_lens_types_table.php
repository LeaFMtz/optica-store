<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 185218 moves prescription_type_id to lens_uses but runs out-of-order on existing DBs.
        // On a fresh DB this migration is responsible for the final state: column on lens_uses.
        if (!Schema::hasColumn($this->prefix.'lens_uses', 'prescription_type_id')) {
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
        if (Schema::hasColumn($this->prefix.'lens_uses', 'prescription_type_id')) {
            Schema::table($this->prefix.'lens_uses', function (Blueprint $table) {
                $table->dropForeign(['prescription_type_id']);
                $table->dropColumn('prescription_type_id');
            });
        }
    }
};
