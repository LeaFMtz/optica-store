<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'lens_type_lens_use', function (Blueprint $table) {
            $table->foreignId('lens_type_id')
                ->constrained($this->prefix.'lens_types')
                ->cascadeOnDelete();
            $table->foreignId('lens_use_id')
                ->constrained($this->prefix.'lens_uses')
                ->cascadeOnDelete();

            $table->primary(['lens_type_id', 'lens_use_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'lens_type_lens_use');
    }
};
