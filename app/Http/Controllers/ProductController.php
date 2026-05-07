<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductLensConfiguration;
use App\Traits\FetchesUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Models\Collection;
use Lunar\Models\Product;

class ProductController extends Controller
{
    use FetchesUrls;

    private function formatPrice(int $cents): string
    {
        return '$ '.number_format($cents / 100, 0, ',', '.');
    }

    private function resolveBannerUrl(Product $product): ?string
    {
        $value = $product->attribute_data->get('detail_banner')?->getValue();
        if (blank($value)) {
            return null;
        }
        // Seeder stores full URLs; Filament FileUpload stores relative disk paths
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return Storage::url($value);
    }

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
        $savingsFormatted = null;
        $discountPercentage = 0;

        if ($variant) {
            $priceRecord = $variant->basePrices->first();
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

        return [
            'id' => $product->id,
            'name' => $product->translateAttribute('name'),
            'description' => $product->translateAttribute('description') ?? '',
            'sku' => $variant?->sku ?? '',
            'price_formatted' => $priceFormatted,
            'base_price_formatted' => $basePriceFormatted,
            'discount_percentage' => $discountPercentage,
            'savings_formatted' => $savingsFormatted,
            'first_variant_id' => $variant?->id,
            'in_stock' => $variant ? $variant->canBeFulfilledAtQuantity(1) : false,
            'purchasable' => $variant?->purchasable,
            'total_inventory' => $variant?->getTotalInventory() ?? 0,
            'detail_banner_url' => $this->resolveBannerUrl($product),
            'attributes' => $this->serializeAttributes($product),
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
            ->where('collection_name', 'images')
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

    /**
     * Build a flat list of display-ready attribute pairs, skipping empty/zero values.
     *
     * @return list<array{label: string, value: string}>
     */
    private function serializeAttributes(Product $product): array
    {
        $data = $product->attribute_data;

        $specs = [
            'shape' => ['label' => 'Forma',          'unit' => null],
            'gender' => ['label' => 'Género',         'unit' => null],
            'material' => ['label' => 'Material',       'unit' => null],
            'frame_size' => ['label' => 'Talle',          'unit' => null],
            'lens_width' => ['label' => 'Ancho de lente', 'unit' => 'mm'],
            'lens_height' => ['label' => 'Alto de lente',  'unit' => 'mm'],
            'frame_width' => ['label' => 'Ancho del marco', 'unit' => 'mm'],
            'bridge_width' => ['label' => 'Puente',         'unit' => 'mm'],
            'temple_length' => ['label' => 'Varilla',        'unit' => 'mm'],
            'frame_weight' => ['label' => 'Peso',           'unit' => 'g'],
        ];

        $result = [];
        foreach ($specs as $key => $config) {
            $attr = $data[$key] ?? null;
            if (!$attr) {
                continue;
            }
            $value = $attr->getValue();
            if ($value === null || $value === '' || $value === 0) {
                continue;
            }
            $display = $config['unit'] ? "{$value} {$config['unit']}" : (string) $value;
            $result[] = ['label' => $config['label'], 'value' => $display];
        }

        return $result;
    }

    /**
     * Serialize a product to the minimal shape expected by ProductCard.vue.
     *
     * @return array{id: int, name: string, slug: string|null, thumbnail_url: string|null, price_formatted: string|null, base_price_formatted: string|null, discount_percentage: int, in_stock: bool}
     */
    private function serializeProductForCard(Product $product): array
    {
        $variant = $product->variants->first();
        $priceFormatted = null;
        $basePriceFormatted = null;
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
            'in_stock' => $variant ? $variant->canBeFulfilledAtQuantity(1) : false,
            'colors' => $colors,
        ];
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

        $productWith = ['thumbnail', 'variants', 'variants.prices', 'variants.values', 'defaultUrl'];

        $featuredCollection = Collection::whereJsonContains('attribute_data->name->value', 'Destacados')->first();
        $featuredProducts = $featuredCollection
            ? $featuredCollection->products()->browsable()->with($productWith)->get()
                ->filter(fn (Product $p) => $p->id !== $product->id)
                ->map(fn (Product $p) => $this->serializeProductForCard($p))
                ->values()
                ->all()
            : [];

        return Inertia::render('Product/Show', [
            'product' => $this->serializeProduct($product),
            'images' => $this->serializeImages($product),
            'options' => $this->serializeOptions($product),
            'hasLensConfigurations' => ProductLensConfiguration::where('product_id', $product->id)->exists(),
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
