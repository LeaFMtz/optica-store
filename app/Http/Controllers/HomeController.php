<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Facades\Pricing;
use Lunar\Models\Collection;

class HomeController extends Controller
{
    /**
     * Serialize a product to a plain array safe for Inertia props.
     *
     * @param  Product  $product
     * @return array{id: int, name: string, slug: string|null, thumbnail_url: string|null, price_formatted: string|null, base_price_formatted: string|null, discount_percentage: int}
     */
    private function serializeProduct($product): array
    {
        $variant = $product->variants->first();
        $priceFormatted = null;
        $basePriceFormatted = null;
        $discountPercentage = 0;

        if ($variant) {
            $pricing = Pricing::for($variant)->get();

            if ($pricing->matched) {
                $priceFormatted = $pricing->matched->price->formatted();
            }

            if ($pricing->base && $pricing->base->price->value > ($pricing->matched?->price->value ?? 0)) {
                $basePriceFormatted = $pricing->base->price->formatted();
                $discountPercentage = (int) round(
                    (($pricing->base->price->value - $pricing->matched->price->value) / $pricing->base->price->value) * 100,
                );
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->translateAttribute('name'),
            'slug' => $product->defaultUrl?->slug,
            'thumbnail_url' => $product->thumbnail?->getUrl('medium'),
            'price_formatted' => $priceFormatted,
            'base_price_formatted' => $basePriceFormatted,
            'discount_percentage' => $discountPercentage,
            'in_stock' => $variant ? $variant->canBeFulfilledAtQuantity(1) : false,
        ];
    }

    public function __invoke(Request $request): Response
    {
        $heroBanners = Banner::where('is_active', true)
            ->where('position', 'home_hero')
            ->orderBy('order')
            ->with('media')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $banner->getFirstMediaUrl('image'),
                'mobile_image_url' => $banner->getFirstMediaUrl('mobile_image') ?: null,
                'url' => $banner->url ?? '#',
            ]);

        $middleBanners = Banner::where('is_active', true)
            ->where('position', 'home_middle')
            ->orderBy('order')
            ->with('media')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $banner->getFirstMediaUrl('image'),
                'mobile_image_url' => $banner->getFirstMediaUrl('mobile_image') ?: null,
                'url' => $banner->url ?? '#',
            ]);

        $bottomBanners = Banner::where('is_active', true)
            ->where('position', 'home_bottom')
            ->orderBy('order')
            ->with('media')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $banner->getFirstMediaUrl('image'),
                'mobile_image_url' => $banner->getFirstMediaUrl('mobile_image') ?: null,
                'url' => $banner->url ?? '#',
            ]);

        $newsletterBannerModel = Banner::where('is_active', true)
            ->where('position', 'home_newsletter')
            ->orderBy('order')
            ->with('media')
            ->first();

        $newsletterBanner = $newsletterBannerModel ? [
            'id' => $newsletterBannerModel->id,
            'title' => $newsletterBannerModel->title,
            'image_url' => $newsletterBannerModel->getFirstMediaUrl('image'),
            'mobile_image_url' => $newsletterBannerModel->getFirstMediaUrl('mobile_image') ?: null,
            'url' => $newsletterBannerModel->url ?? null,
        ] : null;

        $productWith = ['thumbnail', 'variants', 'variants.prices', 'defaultUrl'];

        /** @var Collection|null $featuredCollection */
        $featuredCollection = Collection::whereJsonContains('attribute_data->name->value', 'Destacados')->first();

        $featuredProducts = $featuredCollection
            ? $featuredCollection->products()->browsable()->with($productWith)->get()
                ->map(fn ($p) => $this->serializeProduct($p))->values()->all()
            : [];

        /** @var Collection|null $offersCollection */
        $offersCollection = Collection::whereJsonContains('attribute_data->name->value', 'Ofertas')->first();

        $offerProducts = $offersCollection
            ? $offersCollection->products()->browsable()->with($productWith)->get()
                ->map(fn ($p) => $this->serializeProduct($p))->values()->all()
            : [];

        $homeCollectionIds = Collection::where(function ($q) {
            $q->whereJsonContains('attribute_data->name->value', 'Destacados')
                ->orWhereJsonContains('attribute_data->name->value', 'Ofertas');
        })->pluck('id');

        /** @var Collection|null $randomCollection */
        $randomCollection = Collection::whereNotIn('id', $homeCollectionIds)
            ->inRandomOrder()
            ->first();

        $randomCollectionProducts = [];
        $randomCollectionName = null;
        $randomCollectionSlug = null;

        if ($randomCollection) {
            $randomCollectionName = $randomCollection->translateAttribute('name');
            $randomCollectionSlug = $randomCollection->defaultUrl?->slug;
            $randomCollectionProducts = $randomCollection
                ->products()
                ->browsable()
                ->with($productWith)
                ->get()
                ->map(fn ($product) => $this->serializeProduct($product))
                ->values()
                ->all();
        }

        return Inertia::render('Home/Index', [
            'heroBanners' => $heroBanners,
            'middleBanners' => $middleBanners,
            'bottomBanners' => $bottomBanners,
            'newsletterBanner' => $newsletterBanner,
            'featuredProducts' => $featuredProducts,
            'offerProducts' => $offerProducts,
            'randomCollectionName' => $randomCollectionName,
            'randomCollectionSlug' => $randomCollectionSlug,
            'randomCollectionProducts' => $randomCollectionProducts,
        ]);
    }
}
