<?php

declare(strict_types=1);

namespace App\Providers;

use App\Filament\Resources\BannerResource;
use App\Models\Product;
use App\Modifiers\ShippingModifier;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Base\ShippingModifiers;
use Lunar\Facades\ModelManifest;
use Lunar\Facades\Telemetry;
use Lunar\Shipping\ShippingPlugin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        LunarPanel::panel(
            fn ($panel) => $panel
                ->plugins([
                    new ShippingPlugin,
                ])
                ->resources([
                    BannerResource::class,
                ])
                ->path('panel'),
        )
            ->register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(ShippingModifiers $shippingModifiers): void
    {
        $shippingModifiers->add(
            ShippingModifier::class,
        );

        ModelManifest::replace(
            \Lunar\Models\Contracts\Product::class,
            Product::class,
            // \App\Models\CustomProduct::class,
        );

        Telemetry::optOut();
    }
}
