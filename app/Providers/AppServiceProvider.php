<?php

declare(strict_types=1);

namespace App\Providers;

use App\Filament\Extensions\ManageProductVariantsPageExtension;
use App\Filament\Extensions\ProductOptionResourceExtension;
use App\Filament\Resources\BannerResource;
use App\Filament\Widgets\HierarchicalProductOptionsWidget;
use App\Models\Product;
use App\Models\ProductOption;
use App\Modifiers\ShippingModifier;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\ProductOptionResource;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductVariants;
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

        LunarPanel::extensions([
            ProductOptionResource::class => ProductOptionResourceExtension::class,
            ManageProductVariants::class => ManageProductVariantsPageExtension::class,
        ]);
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

        ModelManifest::replace(
            \Lunar\Models\Contracts\ProductOption::class,
            ProductOption::class,
        );

        Livewire::component(
            'app.filament.widgets.hierarchical-product-options-widget',
            HierarchicalProductOptionsWidget::class,
        );

        Telemetry::optOut();
    }
}
