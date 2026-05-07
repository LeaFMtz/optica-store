<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Dropdown;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->frameAttributeGroupSeeding();
        $this->sunglassesSeeding();
        $this->prescriptionFramesSeeding();
        $this->kidsFramesSeeding();
        $this->clipOnSeeding();
        $this->featuredAndOffersSeeding();
    }

    private function frameAttributeGroupSeeding(): void
    {
        $productType = ProductType::where('name', 'Armazones')->first();

        $group = AttributeGroup::create([
            'attributable_type' => 'product',
            'name' => ['es' => 'Características del Armazón'],
            'handle' => 'caracteristicas-armazon-product',
            'position' => 2,
        ]);

        $attributes = [
            [
                'position' => 1,
                'handle' => 'frame_size',
                'name' => ['es' => 'Tamaño del Marco'],
                'description' => ['es' => 'Tamaño general del armazón'],
                'type' => Dropdown::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'configuration' => [
                    'lookups' => [
                        ['label' => 'Pequeña', 'value' => 'pequena'],
                        ['label' => 'Mediana', 'value' => 'mediana'],
                        ['label' => 'Grande', 'value' => 'grande'],
                    ],
                ],
            ],
            [
                'position' => 2,
                'handle' => 'frame_weight',
                'name' => ['es' => 'Peso del Marco'],
                'description' => ['es' => 'Peso del armazón en gramos'],
                'type' => Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'configuration' => [],
            ],
            [
                'position' => 3,
                'handle' => 'material',
                'name' => ['es' => 'Material'],
                'description' => ['es' => 'Material del armazón'],
                'type' => Dropdown::class,
                'required' => false,
                'searchable' => true,
                'filterable' => true,
                'system' => false,
                'configuration' => [
                    'lookups' => [
                        ['label' => 'Acetato', 'value' => 'acetato'],
                        ['label' => 'Metal', 'value' => 'metal'],
                        ['label' => 'TR90', 'value' => 'tr90'],
                        ['label' => 'TR90 + Metal', 'value' => 'tr90-metal'],
                        ['label' => 'Nylon', 'value' => 'nylon'],
                        ['label' => 'Policarbonato', 'value' => 'policarbonato'],
                        ['label' => 'Ultem', 'value' => 'ultem'],
                    ],
                ],
            ],
            [
                'position' => 4,
                'handle' => 'shape',
                'name' => ['es' => 'Forma'],
                'description' => ['es' => 'Forma del armazón'],
                'type' => Dropdown::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'configuration' => [
                    'lookups' => [
                        ['label' => 'Cuadrado', 'value' => 'cuadrado'],
                        ['label' => 'Rectangular', 'value' => 'rectangular'],
                        ['label' => 'Redondo', 'value' => 'redondo'],
                        ['label' => 'Aviador', 'value' => 'aviador'],
                        ['label' => 'Ojo de gato', 'value' => 'ojo-de-gato'],
                        ['label' => 'Wayfarer', 'value' => 'wayfarer'],
                        ['label' => 'Deportivo', 'value' => 'deportivo'],
                        ['label' => 'Hexagonal', 'value' => 'hexagonal'],
                        ['label' => 'Ovalado', 'value' => 'ovalado'],
                    ],
                ],
            ],
            [
                'position' => 5,
                'handle' => 'gender',
                'name' => ['es' => 'Género'],
                'description' => ['es' => 'Género al que está orientado el armazón'],
                'type' => Dropdown::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'configuration' => [
                    'lookups' => [
                        ['label' => 'Masculino', 'value' => 'masculino'],
                        ['label' => 'Femenino', 'value' => 'femenino'],
                        ['label' => 'Unisex', 'value' => 'unisex'],
                        ['label' => 'Niños', 'value' => 'ninos'],
                    ],
                ],
            ],
            [
                'position' => 6,
                'handle' => 'frame_width',
                'name' => ['es' => 'Ancho del Marco'],
                'description' => ['es' => 'Ancho total del marco en mm'],
                'type' => Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'configuration' => [],
            ],
            [
                'position' => 7,
                'handle' => 'lens_height',
                'name' => ['es' => 'Altura de la Lente'],
                'description' => ['es' => 'Altura de la lente en mm'],
                'type' => Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'configuration' => [],
            ],
            [
                'position' => 8,
                'handle' => 'lens_width',
                'name' => ['es' => 'Ancho de la Lente'],
                'description' => ['es' => 'Ancho de la lente en mm'],
                'type' => Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'configuration' => [],
            ],
            [
                'position' => 9,
                'handle' => 'bridge_width',
                'name' => ['es' => 'Ancho del Puente'],
                'description' => ['es' => 'Ancho del puente nasal en mm'],
                'type' => Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'configuration' => [],
            ],
            [
                'position' => 10,
                'handle' => 'temple_length',
                'name' => ['es' => 'Longitud del Brazo'],
                'description' => ['es' => 'Longitud de los brazos en mm'],
                'type' => Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'configuration' => [],
            ],
            [
                'position' => 11,
                'handle' => 'rim_type',
                'name' => ['es' => 'Llanta'],
                'description' => ['es' => 'Tipo de llanta del armazón'],
                'type' => Dropdown::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'configuration' => [
                    'lookups' => [
                        ['label' => 'Llanta completa', 'value' => 'llanta-completa'],
                        ['label' => 'Semi llanta', 'value' => 'semi-llanta'],
                        ['label' => 'Sin llanta', 'value' => 'sin-llanta'],
                    ],
                ],
            ],
            [
                'position' => 12,
                'handle' => 'color',
                'name' => ['es' => 'Color'],
                'description' => ['es' => 'Color del armazón'],
                'type' => Dropdown::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'configuration' => [
                    'lookups' => [
                        ['label' => 'Negro', 'value' => 'negro'],
                        ['label' => 'Negro Mate', 'value' => 'negro-mate'],
                        ['label' => 'Blanco', 'value' => 'blanco'],
                        ['label' => 'Gris', 'value' => 'gris'],
                        ['label' => 'Dorado', 'value' => 'dorado'],
                        ['label' => 'Plateado', 'value' => 'plateado'],
                        ['label' => 'Marrón Havana', 'value' => 'marron-havana'],
                        ['label' => 'Borgoña', 'value' => 'borgona'],
                        ['label' => 'Azul', 'value' => 'azul'],
                        ['label' => 'Rosa', 'value' => 'rosa'],
                        ['label' => 'Lila', 'value' => 'lila'],
                        ['label' => 'Rojo', 'value' => 'rojo'],
                        ['label' => 'Verde', 'value' => 'verde'],
                        ['label' => 'Naranja', 'value' => 'naranja'],
                        ['label' => 'Transparente', 'value' => 'transparente'],
                        ['label' => 'Beige', 'value' => 'beige'],
                    ],
                ],
            ],
        ];

        foreach ($attributes as $attr) {
            $attribute = $group->attributes()->create(array_merge($attr, ['attribute_type' => 'product']));
            $productType?->mappedAttributes()->attach($attribute->id);
        }
    }

    private function sunglassesSeeding(): void
    {
        $productTypeId = ProductType::where('name', 'Armazones')->value('id');
        $rayBan = Brand::where('name', 'Ray-Ban')->value('id');
        $rusty = Brand::where('name', 'Rusty')->value('id');
        $vogue = Brand::where('name', 'Vogue')->value('id');
        $collectionId = Collection::whereJsonContains('attribute_data->name->value', 'Lentes de Sol')->value('id') ?? 3;

        $this->createProduct($productTypeId, $rayBan, 'RB3025', 'Ray-Ban Aviador RB3025', 'Clásico aviador en metal dorado con lentes verdes G-15', 85000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'metal', 'shape' => 'aviador',
            'gender' => 'unisex', 'frame_width' => 140, 'lens_height' => 52,
            'lens_width' => 58, 'bridge_width' => 14, 'temple_length' => 135,
            'rim_type' => 'llanta-completa', 'color' => 'dorado',
        ], images: ['sol-1a.jpg', 'sol-1b.jpg']);

        $this->createProduct($productTypeId, $rayBan, 'RB2140', 'Ray-Ban Wayfarer RB2140', 'Icónico wayfarer en acetato negro con lentes grises', 78000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'wayfarer',
            'gender' => 'unisex', 'frame_width' => 142, 'lens_height' => 45,
            'lens_width' => 50, 'bridge_width' => 22, 'temple_length' => 150,
            'rim_type' => 'llanta-completa', 'color' => 'negro',
        ], images: ['sol-2a.jpg', 'sol-2b.jpg']);

        $this->createProduct($productTypeId, $rusty, 'RS401', 'Rusty Surfboard RS-401', 'Diseño deportivo con protección UV400 para actividades al aire libre', 45000, $collectionId, [
            'frame_size' => 'grande', 'material' => 'tr90', 'shape' => 'deportivo',
            'gender' => 'unisex', 'frame_width' => 145, 'lens_height' => 48,
            'lens_width' => 60, 'bridge_width' => 16, 'temple_length' => 130,
            'rim_type' => 'semi-llanta', 'color' => 'negro-mate',
        ], images: ['sol-3a.jpg', 'sol-3b.jpg']);

        $this->createProduct($productTypeId, $vogue, 'VO5051S', 'Vogue VO5051S', 'Elegante diseño cat-eye en acetato marrón havana', 55000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'ojo-de-gato',
            'gender' => 'femenino', 'frame_width' => 138, 'lens_height' => 44,
            'lens_width' => 54, 'bridge_width' => 16, 'temple_length' => 140,
            'rim_type' => 'llanta-completa', 'color' => 'marron-havana',
        ], images: ['sol-1a.jpg', 'sol-2a.jpg']);
    }

    private function prescriptionFramesSeeding(): void
    {
        $productTypeId = ProductType::where('name', 'Armazones')->value('id');
        $rayBan = Brand::where('name', 'Ray-Ban')->value('id');
        $vogue = Brand::where('name', 'Vogue')->value('id');
        $vulk = Brand::where('name', 'Vulk')->value('id');
        $collectionId = Collection::whereJsonContains('attribute_data->name->value', 'Armazones de Receta')->value('id') ?? 5;

        $this->createProduct($productTypeId, $rayBan, 'RX5228', 'Ray-Ban RX5228', 'Armazón cuadrado clásico en acetato negro para receta oftálmica', 62000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'cuadrado',
            'gender' => 'unisex', 'frame_weight' => 28, 'frame_width' => 140,
            'lens_height' => 42, 'lens_width' => 52, 'bridge_width' => 18,
            'temple_length' => 145, 'rim_type' => 'llanta-completa', 'color' => 'negro',
        ], images: ['frame-1a.jpg']);

        $this->createProduct($productTypeId, $vogue, 'VO5184', 'Vogue VO5184', 'Armazón rectangular femenino en acetato borgoña', 48000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'acetato', 'shape' => 'rectangular',
            'gender' => 'femenino', 'frame_weight' => 22, 'frame_width' => 134,
            'lens_height' => 38, 'lens_width' => 50, 'bridge_width' => 17,
            'temple_length' => 140, 'rim_type' => 'llanta-completa', 'color' => 'borgona',
        ], images: ['frame-2a.jpg', 'frame-2b.jpg']);

        $this->createProduct($productTypeId, $vulk, 'VKCLASS', 'Vulk VK-Classic', 'Armazón redondo en metal dorado, estilo vintage retro', 35000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'metal', 'shape' => 'redondo',
            'gender' => 'unisex', 'frame_weight' => 18, 'frame_width' => 136,
            'lens_height' => 44, 'lens_width' => 46, 'bridge_width' => 19,
            'temple_length' => 140, 'rim_type' => 'llanta-completa', 'color' => 'dorado',
        ], images: ['frame-1a.jpg']);

        $this->createProduct($productTypeId, $vulk, 'VKMETRO', 'Vulk VK-Metro', 'Armazón cat-eye en acetato transparente con detalles dorados', 38000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'ojo-de-gato',
            'gender' => 'femenino', 'frame_weight' => 24, 'frame_width' => 138,
            'lens_height' => 40, 'lens_width' => 52, 'bridge_width' => 17,
            'temple_length' => 142, 'rim_type' => 'llanta-completa', 'color' => 'transparente',
        ], images: ['frame-2a.jpg', 'frame-2b.jpg']);
    }

    private function kidsFramesSeeding(): void
    {
        $productTypeId = ProductType::where('name', 'Armazones')->value('id');
        $rusty = Brand::where('name', 'Rusty')->value('id');
        $vogue = Brand::where('name', 'Vogue')->value('id');
        $rayBan = Brand::where('name', 'Ray-Ban')->value('id');
        $collectionId = Collection::whereJsonContains('attribute_data->name->value', 'Niños')->value('id') ?? 4;

        $this->createProduct($productTypeId, $rusty, 'RKFUN', 'Rusty Kids RK-Fun', 'Armazón flexible y resistente en TR90 para niños activos', 28000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'tr90', 'shape' => 'rectangular',
            'gender' => 'ninos', 'frame_weight' => 14, 'frame_width' => 120,
            'lens_height' => 34, 'lens_width' => 46, 'bridge_width' => 14,
            'temple_length' => 125, 'rim_type' => 'llanta-completa', 'color' => 'azul',
        ], images: ['kids-1a.jpg', 'kids-1b.jpg']);

        $this->createProduct($productTypeId, $vogue, 'VJSTAR', 'Vogue Junior VJ-Star', 'Diseño cat-eye colorido para niñas, liviano y seguro', 32000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'acetato', 'shape' => 'ojo-de-gato',
            'gender' => 'ninos', 'frame_weight' => 16, 'frame_width' => 118,
            'lens_height' => 32, 'lens_width' => 44, 'bridge_width' => 13,
            'temple_length' => 120, 'rim_type' => 'llanta-completa', 'color' => 'rosa',
        ], images: ['kids-2a.jpg', 'kids-2b.jpg']);

        $this->createProduct($productTypeId, $rayBan, 'RJ9050S', 'Ray-Ban Junior RJ9050S', 'Lente de sol infantil con protección UV400', 42000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'nylon', 'shape' => 'wayfarer',
            'gender' => 'ninos', 'frame_weight' => 18, 'frame_width' => 122,
            'lens_height' => 36, 'lens_width' => 48, 'bridge_width' => 15,
            'temple_length' => 125, 'rim_type' => 'llanta-completa', 'color' => 'negro',
        ], images: ['kids-1a.jpg', 'kids-2a.jpg']);
    }

    private function clipOnSeeding(): void
    {
        $productTypeId = ProductType::where('name', 'Armazones')->value('id');
        $vulk = Brand::where('name', 'Vulk')->value('id');
        $collectionId = Collection::whereJsonContains('attribute_data->name->value', 'Armazones Clip On')->value('id') ?? 6;

        $this->createProduct($productTypeId, $vulk, 'VCO01', 'Vulk Clip-On VCO-01', 'Clip-on magnético sin llanta para convertir armazones de receta en solares', 25000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'metal', 'shape' => 'rectangular',
            'gender' => 'unisex', 'frame_weight' => 8, 'frame_width' => 138,
            'lens_height' => 40, 'lens_width' => 50,
            'rim_type' => 'sin-llanta', 'color' => 'negro',
        ], images: ['frame-1a.jpg']);

        $this->createProduct($productTypeId, $vulk, 'VCO02', 'Vulk Clip-On VCO-02', 'Clip-on semi llanta con bisagra doble para mayor estabilidad', 28000, $collectionId, [
            'frame_size' => 'grande', 'material' => 'metal', 'shape' => 'cuadrado',
            'gender' => 'unisex', 'frame_weight' => 10, 'frame_width' => 142,
            'lens_height' => 42, 'lens_width' => 54,
            'rim_type' => 'semi-llanta', 'color' => 'plateado',
        ], images: ['frame-2a.jpg', 'frame-2b.jpg']);
    }

    private function featuredAndOffersSeeding(): void
    {
        $featured = Collection::whereJsonContains('attribute_data->name->value', 'Destacados')->first();
        $offers = Collection::whereJsonContains('attribute_data->name->value', 'Ofertas')->first();

        $featuredSkus = ['RB3025', 'VO5051S', 'RX5228', 'VKCLASS', 'RKFUN'];

        foreach ($featuredSkus as $sku) {
            $productId = ProductVariant::where('sku', $sku)->value('product_id');
            if ($productId && $featured) {
                Product::find($productId)?->collections()->attach($featured->id);
            }
        }

        // compare_price = precio original antes del descuento (en centavos)
        $offerSkus = [
            'RB2140' => 9500000,
            'VO5184' => 5800000,
            'VJSTAR' => 4200000,
            'VCO02'  => 3500000,
        ];

        foreach ($offerSkus as $sku => $comparePriceRaw) {
            $variant = ProductVariant::where('sku', $sku)->first();
            if (! $variant) {
                continue;
            }

            $variant->prices()->update(['compare_price' => $comparePriceRaw]);

            if ($offers) {
                Product::find($variant->product_id)?->collections()->attach($offers->id);
            }
        }
    }

    /**
     * @param array<string, string|int|float> $frameAttrs
     * @param array<int, string> $images
     */
    private function createProduct(
        int $productTypeId,
        ?int $brandId,
        string $sku,
        string $name,
        string $description,
        int $priceArs,
        int $collectionId,
        array $frameAttrs = [],
        int $stock = 10,
        array $images = [],
    ): void {
        $taxClass = TaxClass::first();
        $currency = Currency::where('default', true)->first();

        $attributeData = [
            'name' => new Text($name),
            'description' => new Text($description),
        ];

        foreach ($frameAttrs as $handle => $value) {
            $attributeData[$handle] = is_numeric($value)
                ? new Number((float) $value)
                : new Dropdown((string) $value);
        }

        $product = Product::create([
            'product_type_id' => $productTypeId,
            'brand_id' => $brandId,
            'status' => 'published',
            'attribute_data' => $attributeData,
        ]);

        $variant = $product->variants()->create([
            'tax_class_id' => $taxClass->id,
            'sku' => $sku,
            'stock' => $stock,
            'backorder' => 0,
            'purchasable' => 'in_stock_or_on_backorder',
        ]);

        $variant->prices()->create([
            'currency_id' => $currency->id,
            'price' => $priceArs * 100,
            'min_quantity' => 1,
        ]);

        $product->collections()->attach($collectionId);

        foreach ($images as $image) {
            $product->addMedia(database_path("seeders/assets/banners/frames/{$image}"))
                ->preservingOriginal()
                ->toMediaCollection('images');
        }
    }
}
