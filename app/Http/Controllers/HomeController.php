<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Facades\Pricing;
use Lunar\Models\Collection;
use Lunar\Models\Product;
use Lunar\Models\Url;

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
        ];
    }

    public function __invoke(Request $request): Response
    {
        $heroBanners = Banner::where('is_active', true)
            ->where('position', 'home_hero')
            ->orderBy('order')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => asset('storage/'.$banner->image_path),
                'url' => $banner->url ?? '#',
            ]);

        $middleBanners = Banner::where('is_active', true)
            ->where('position', 'home_middle')
            ->orderBy('order')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => asset('storage/'.$banner->image_path),
                'url' => $banner->url ?? '#',
            ]);

        $bottomBanners = Banner::where('is_active', true)
            ->where('position', 'home_bottom')
            ->orderBy('order')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => asset('storage/'.$banner->image_path),
                'url' => $banner->url ?? '#',
            ]);

        /** @var Collection|null $saleCollection */
        $saleCollection = Url::whereElementType((new Collection)->getMorphClass())
            ->whereSlug('sale')
            ->first()
            ?->element;

        $saleProducts = [];

        if ($saleCollection) {
            $saleProducts = $saleCollection
                ->products()
                ->with(['thumbnail', 'variants', 'variants.prices', 'defaultUrl'])
                ->get()
                ->map(fn ($product) => $this->serializeProduct($product))
                ->values()
                ->all();
        }

        $randomCollectionQuery = Url::whereElementType((new Collection)->getMorphClass());

        if ($saleCollection) {
            $randomCollectionQuery->where('element_id', '!=', $saleCollection->id);
        }

        /** @var Collection|null $randomCollection */
        $randomCollection = $randomCollectionQuery->inRandomOrder()->first()?->element;

        $randomCollectionProducts = [];
        $randomCollectionName = null;
        $randomCollectionSlug = null;

        if ($randomCollection) {
            $randomCollectionName = $randomCollection->translateAttribute('name');
            $randomCollectionSlug = $randomCollection->defaultUrl?->slug;
            $randomCollectionProducts = $randomCollection
                ->products()
                ->with(['thumbnail', 'variants', 'variants.prices', 'defaultUrl'])
                ->get()
                ->map(fn ($product) => $this->serializeProduct($product))
                ->values()
                ->all();
        }

        return Inertia::render('Home/Index', [
            'heroBanners' => $heroBanners,
            'middleBanners' => $middleBanners,
            'bottomBanners' => $bottomBanners,
            'saleProducts' => $saleProducts,
            'saleCollectionSlug' => $saleCollection?->defaultUrl?->slug,
            'randomCollectionName' => $randomCollectionName,
            'randomCollectionSlug' => $randomCollectionSlug,
            'randomCollectionProducts' => $randomCollectionProducts,
        ]);
    }
}
