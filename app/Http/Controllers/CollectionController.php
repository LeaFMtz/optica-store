<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Facades\Pricing;
use Lunar\Models\Collection as CollectionModel;
use Lunar\Models\Product;
use Lunar\Models\Url;

class CollectionController extends Controller
{
    /**
     * Serialize a product to a plain array safe for Inertia props.
     *
     * @return array{id: int, name: string, slug: string|null, thumbnail_url: string|null, price_formatted: string|null, base_price_formatted: string|null, discount_percentage: int}
     */
    private function serializeProduct(Product $product): array
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

    public function __invoke(Request $request, string $slug): Response
    {
        /** @var Url|null $url */
        $url = Url::whereElementType((new CollectionModel)->getMorphClass())
            ->whereDefault(true)
            ->whereSlug($slug)
            ->with([
                'element.thumbnail',
                'element.products.variants.basePrices',
                'element.products.defaultUrl',
                'element.products.thumbnail',
            ])
            ->first();

        if (!$url) {
            abort(404);
        }

        /** @var CollectionModel $collection */
        $collection = $url->element;

        $products = $collection->products
            ->map(fn (Product $product) => $this->serializeProduct($product))
            ->values()
            ->all();

        return Inertia::render('Collection/Show', [
            'collection' => [
                'name' => $collection->translateAttribute('name'),
                'slug' => $slug,
                'product_count' => count($products),
            ],
            'products' => $products,
        ]);
    }
}
