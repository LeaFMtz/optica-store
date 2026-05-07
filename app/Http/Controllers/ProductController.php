<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductLensConfiguration;
use App\Traits\FetchesUrls;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Facades\Pricing;
use Lunar\Models\Product;

class ProductController extends Controller
{
    use FetchesUrls;

    /**
     * Serialize core product fields for the Vue component.
     *
     * @return array{id: int, name: string, description: string, sku: string, price_formatted: string|null, base_price_formatted: string|null, discount_percentage: int}
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
            'description' => $product->translateAttribute('description') ?? '',
            'sku' => $variant?->sku ?? '',
            'price_formatted' => $priceFormatted,
            'base_price_formatted' => $basePriceFormatted,
            'discount_percentage' => $discountPercentage,
            'first_variant_id'  => $variant?->id,
            'in_stock'          => $variant ? $variant->canBeFulfilledAtQuantity(1) : false,
            'purchasable'       => $variant?->purchasable,
            'total_inventory'   => $variant?->getTotalInventory() ?? 0,
            'variants' => $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'value_ids' => $v->values->pluck('id')->all(),
            ])->values()->all(),
        ];
    }

    /**
     * Serialize all product media ordered by order_column.
     *
     * @return list<array{id: int, url_large: string, url_small: string, is_primary: bool}>
     */
    private function serializeImages(Product $product): array
    {
        return $product->media
            ->sortBy('order_column')
            ->map(fn ($media) => [
                'id' => $media->id,
                'url_large' => $media->getUrl('large'),
                'url_small' => $media->getUrl('small'),
                'is_primary' => (bool) $media->getCustomProperty('primary'),
            ])
            ->values()
            ->all();
    }

    /**
     * Serialize product options with their values, grouped by option.
     * Each option lists only the values present across all variants.
     *
     * @return list<array{option_id: int, option_name: string, values: list<array{id: int, name: string}>}>
     */
    private function serializeOptions(Product $product): array
    {
        $allValues = $product->variants->pluck('values')->flatten()->unique('id');

        return $allValues
            ->groupBy('product_option_id')
            ->map(function ($values) {
                $firstValue = $values->first();

                return [
                    'option_id' => $firstValue->option->id,
                    'option_name' => $firstValue->option->translate('name'),
                    'values' => $values->map(fn ($v) => [
                        'id' => $v->id,
                        'name' => $v->translate('name'),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    public function __invoke(Request $request, string $slug): Response
    {
        $url = $this->fetchUrl(
            $slug,
            (new Product)->getMorphClass(),
            [
                'element.media',
                'element.variants.basePrices.currency',
                'element.variants.basePrices.priceable',
                'element.variants.values.option',
            ],
        );

        if (!$url) {
            abort(404);
        }

        /** @var Product $product */
        $product = $url->element;

        return Inertia::render('Product/Show', [
            'product' => $this->serializeProduct($product),
            'images' => $this->serializeImages($product),
            'options' => $this->serializeOptions($product),
            'hasLensConfigurations' => ProductLensConfiguration::where('product_id', $product->id)->exists(),
        ]);
    }
}
