<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Facades\Pricing;

class SearchController extends Controller
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

    public function __invoke(Request $request): Response
    {
        $query = $request->input('q', '');

        $results = [];

        if ($query !== '') {
            $paginator = Product::search($query)
                ->query(fn ($builder) => $builder->with(['variants.basePrices', 'defaultUrl', 'thumbnail']))
                ->paginate(50);

            $results = $paginator->getCollection()
                ->map(fn (Product $product) => $this->serializeProduct($product))
                ->values()
                ->all();
        }

        return Inertia::render('Search/Index', [
            'results' => $results,
            'query' => $query,
        ]);
    }
}
