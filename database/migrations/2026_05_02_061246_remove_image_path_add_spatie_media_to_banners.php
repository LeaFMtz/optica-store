<?php

declare(strict_types=1);

use App\Models\Banner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Base\Migration;

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

                if (!$imagePath) {
                    return;
                }

                try {
                    $banner->addMediaFromDisk($imagePath, 'public')
                        ->preservingOriginal()
                        ->toMediaCollection('image');
                } catch (Throwable $e) {
                    Log::warning("Banner migration: skipping banner #{$banner->id} — file '{$imagePath}' not found on disk. Error: {$e->getMessage()}");
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Banner::all()->each(function (Banner $banner) {
            $path = $banner->getFirstMediaPath('image');

            if ($path) {
                $banner->update(['image_path' => $path]);
            }
        });
    }
};
