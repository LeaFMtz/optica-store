<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'prescription_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->decimal('min', 8, 2);
            $table->decimal('max', 8, 2);
            $table->decimal('step', 8, 2)->default(0.25);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create($this->prefix.'prescription_type_prescription_field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_type_id')
                ->constrained($this->prefix.'prescription_types')
                ->cascadeOnDelete();
            $table->foreignId('prescription_field_id')
                ->constrained($this->prefix.'prescription_fields')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['prescription_type_id', 'prescription_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'prescription_type_prescription_field');
        Schema::dropIfExists($this->prefix.'prescription_fields');
    }
};
