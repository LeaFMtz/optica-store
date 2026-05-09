<?php

declare(strict_types=1);

namespace App\Providers;

use App\Filament\Resources\BannerResource;
use App\Filament\Resources\LensTypeResource;
use App\Filament\Resources\LensUseResource;
use App\Filament\Resources\OrderResource\Pages\Components\OrderItemsTableExtension;
use App\Filament\Resources\OrderResource\Pages\ManageOrderExtension;
use App\Filament\Resources\PrescriptionFieldResource;
use App\Filament\Resources\PrescriptionTypeResource;
use App\Filament\Resources\ProductResourceExtension;
use Lunar\Facades\Payments;
use App\Filament\Support\FieldTypes\ImageFileField;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\Components\OrderItemsTable;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\ManageOrder;

use App\Models\Product as AppProduct;
use App\Modifiers\ShippingModifier;
use App\PaymentTypes\MercadoPagoPayment;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Support\Facades\AttributeData;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Base\ShippingModifiers;
use Lunar\Facades\ModelManifest;
use Lunar\Facades\Telemetry;
use Lunar\FieldTypes\File as FileFieldType;
use Lunar\Models\Contracts\Product;
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
                    LensUseResource::class,
                    LensTypeResource::class,
                    PrescriptionTypeResource::class,
                    PrescriptionFieldResource::class,
                    ProductResourceExtension::class,
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

        LunarPanel::extensions([
            OrderItemsTable::class => OrderItemsTableExtension::class,
            ManageOrder::class => ManageOrderExtension::class,
        ]);

        AttributeData::registerFieldType(FileFieldType::class, ImageFileField::class);

        ModelManifest::replace(
            Product::class,
            AppProduct::class,
        );

        Telemetry::optOut();
        Payments::extend('mercadopago', fn ($app) => $app->make(MercadoPagoPayment::class));
    }
}
