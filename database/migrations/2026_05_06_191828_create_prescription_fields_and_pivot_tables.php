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
            $table->unsignedBigInteger('prescription_type_id');
            $table->unsignedBigInteger('prescription_field_id');
            $table->unsignedInteger('sort_order')->default(0);

            $table->foreign('prescription_type_id', 'opt_pt_pf_type_fk')
                ->references('id')->on($this->prefix.'prescription_types')
                ->cascadeOnDelete();
            $table->foreign('prescription_field_id', 'opt_pt_pf_field_fk')
                ->references('id')->on($this->prefix.'prescription_fields')
                ->cascadeOnDelete();

            $table->unique(['prescription_type_id', 'prescription_field_id'], 'opt_pt_pf_unique');
        });

        Schema::table($this->prefix.'prescription_types', function (Blueprint $table) {
            $table->dropColumn('prescription_fields');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'prescription_types', function (Blueprint $table) {
            $table->json('prescription_fields')->nullable();
        });

        Schema::dropIfExists($this->prefix.'prescription_type_prescription_field');
        Schema::dropIfExists($this->prefix.'prescription_fields');
    }
};
