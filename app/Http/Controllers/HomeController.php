<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Models\Collection;
use Lunar\Models\Product;

class HomeController extends Controller
{
    private function formatPrice(int $cents): string
    {
        return '$ '.number_format($cents / 100, 0, ',', '.');
    }

    /**
     * Serialize a product to a plain array safe for Inertia props.
     *
     * @return array{id: int, name: string, slug: string|null, thumbnail_url: string|null, price_formatted: string|null, base_price_formatted: string|null, discount_percentage: int, savings_formatted: string|null, in_stock: bool}
     */
    private function serializeProduct(Product $product): array
    {
        $variant = $product->variants->first();
        $priceFormatted = null;
        $basePriceFormatted = null;
        $savingsFormatted = null;
        $discountPercentage = 0;

        if ($variant) {
            $priceRecord = $variant->prices->first();
            $currentValue = $priceRecord?->price?->value ?? 0;
            $compareValue = $priceRecord?->compare_price?->value ?? 0;

            if ($currentValue > 0) {
                $priceFormatted = $this->formatPrice($currentValue);
            }

            if ($compareValue > 0 && $compareValue > $currentValue) {
                $basePriceFormatted = $this->formatPrice($compareValue);
                $discountPercentage = (int) round(($compareValue - $currentValue) / $compareValue * 100);
                $savingsFormatted = $this->formatPrice($compareValue - $currentValue);
            }
        }

        $colors = $product->variants
            ->flatMap(fn ($v) => $v->values)
            ->unique('id')
            ->map(fn ($v) => $v->translate('name'))
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $product->id,
            'name' => $product->translateAttribute('name'),
            'slug' => $product->defaultUrl?->slug,
            'thumbnail_url' => $product->thumbnail?->getUrl('medium'),
            'price_formatted' => $priceFormatted,
            'base_price_formatted' => $basePriceFormatted,
            'discount_percentage' => $discountPercentage,
            'savings_formatted' => $savingsFormatted,
            'in_stock' => $variant ? $variant->canBeFulfilledAtQuantity(1) : false,
            'colors' => $colors,
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

        $productWith = ['thumbnail', 'variants', 'variants.prices', 'variants.values', 'defaultUrl'];

        /** @var Collection|null $featuredCollection */
        $featuredCollection = Collection::whereJsonContains('attribute_data->name->value', 'Destacados')->first();

        $featuredProducts = $featuredCollection
            ? $featuredCollection->products()->browsable()->with($productWith)->get()
                ->map(fn ($p) => $this->serializeProduct($p))->values()->all()
            : [];

        $offerProducts = Product::query()
            ->whereHas('variants.prices', function ($q) {
                $q->whereColumn('compare_price', '>', 'price')->where('compare_price', '>', 0);
            })
            ->with($productWith)
            ->get()
            ->map(fn (Product $p) => $this->serializeProduct($p))
            ->values()
            ->all();

        $homeCollectionIds = Collection::whereJsonContains('attribute_data->name->value', 'Destacados')
            ->pluck('id');

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
