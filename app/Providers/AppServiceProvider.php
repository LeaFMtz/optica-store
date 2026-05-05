<?php

declare(strict_types=1);

namespace App\Providers;

use App\Filament\Resources\BannerResource;
use App\Modifiers\ShippingModifier;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Base\ShippingModifiers;
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
                ->path('panel')
                ->brandName('Óptica Guzmán')
                ->brandLogo(asset('images/logo-light.png'))
                ->darkModeBrandLogo(asset('images/logo.webp'))
                ->favicon(asset('favicon.png'))
                ->brandLogoHeight('3.5rem')
                ->colors(['primary' => '#427318'])
                ->font(null),
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

        Telemetry::optOut();
    }
}
