<?php

declare(strict_types=1);

use App\Models\Banner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            Banner::all()->each(function (Banner $banner) {
                /** @var string|null $imagePath */
                $imagePath = $banner->getRawOriginal('image_path');

                if (! $imagePath) {
                    return;
                }

                try {
                    $banner->addMediaFromDisk($imagePath, 'public')
                        ->preservingOriginal()
                        ->toMediaCollection('image');
                } catch (\Throwable $e) {
                    Log::warning("Banner migration: skipping banner #{$banner->id} — file '{$imagePath}' not found on disk. Error: {$e->getMessage()}");
                }
            });
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('image_path')->nullable();
        });

        Banner::all()->each(function (Banner $banner) {
            $path = $banner->getFirstMediaPath('image');

            if ($path) {
                $banner->update(['image_path' => $path]);
            }
        });
    }
};
