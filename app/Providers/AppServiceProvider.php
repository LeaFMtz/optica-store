<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Product;
use App\Modifiers\ShippingModifier;
use App\PaymentTypes\MercadoPagoPayment;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Base\ShippingModifiers;
use Lunar\Facades\ModelManifest;
use Lunar\Facades\Payments;
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
            fn ($panel) => $panel->plugins([
                new ShippingPlugin,
            ]),
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
        );

        Telemetry::optOut();

        Payments::extend('mercadopago', function ($app) {
            return new MercadoPagoPayment;
        });
    }
}
