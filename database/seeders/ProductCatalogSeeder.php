<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Dropdown;
use Lunar\FieldTypes\File;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

class ProductCatalogSeeder extends Seeder
{
    /** @var array<string, ProductOptionValue> */
    private array $colorValues = [];

    /** @var array<string, int> */
    private array $productsByBaseSku = [];

    private ?ProductOption $colorOption = null;

    public function run(): void
    {
        $this->createColorOption();
        $this->frameAttributeGroupSeeding();
        $this->sunglassesSeeding();
        $this->prescriptionFramesSeeding();
        $this->kidsFramesSeeding();
        $this->clipOnSeeding();
        $this->featuredAndOffersSeeding();
    }

    private function createColorOption(): void
    {
        $this->colorOption = ProductOption::create([
            'name' => ['es' => 'Color'],
            'handle' => 'color',
        ]);
        $option = $this->colorOption;

        $colors = [
            'negro' => 'Negro',
            'negro-mate' => 'Negro Mate',
            'blanco' => 'Blanco',
            'gris' => 'Gris',
            'dorado' => 'Dorado',
            'plateado' => 'Plateado',
            'marron-havana' => 'Marrón Havana',
            'borgona' => 'Borgoña',
            'azul' => 'Azul',
            'rosa' => 'Rosa',
            'lila' => 'Lila',
            'rojo' => 'Rojo',
            'verde' => 'Verde',
            'naranja' => 'Naranja',
            'transparente' => 'Transparente',
            'beige' => 'Beige',
        ];

        $position = 1;
        foreach ($colors as $handle => $label) {
            $value = ProductOptionValue::create([
                'product_option_id' => $option->id,
                'name' => ['es' => $label],
                'position' => $position++,
            ]);
            $this->colorValues[$handle] = $value;
        }
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
                'handle' => 'detail_banner',
                'name' => ['es' => 'Banner de Detalle'],
                'description' => ['es' => 'Imagen promocional para la página del producto'],
                'type' => File::class,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'system' => false,
                'configuration' => [
                    'file_types' => ['image/jpeg', 'image/png', 'image/webp'],
                    'multiple' => false,
                    'max_files' => 1,
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
        $vulk = Brand::where('name', 'Vulk')->value('id');
        $collectionId = Collection::whereJsonContains('attribute_data->name->value', 'Lentes de Sol')->value('id') ?? 3;

        $this->createProduct($productTypeId, $rayBan, 'RB3025', 'Ray-Ban Aviador RB3025', 'Clásico aviador en metal con lentes verdes G-15', 85000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'metal', 'shape' => 'aviador',
            'gender' => 'unisex', 'frame_width' => 140, 'lens_height' => 52,
            'lens_width' => 58, 'bridge_width' => 14, 'temple_length' => 135,
            'rim_type' => 'llanta-completa',
        ], images: ['sol-1a.jpg', 'sol-1b.jpg'], colors: ['dorado', 'plateado', 'negro']);

        $this->createProduct($productTypeId, $rayBan, 'RB2140', 'Ray-Ban Wayfarer RB2140', 'Icónico wayfarer en acetato, lentes grises de alto contraste', 78000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'wayfarer',
            'gender' => 'unisex', 'frame_width' => 142, 'lens_height' => 45,
            'lens_width' => 50, 'bridge_width' => 22, 'temple_length' => 150,
            'rim_type' => 'llanta-completa',
        ], images: ['sol-2a.jpg', 'sol-2b.jpg'], detailBanner: 'mid-banner-0.webp', colors: ['negro', 'marron-havana', 'azul']);

        $this->createProduct($productTypeId, $rusty, 'RS401', 'Rusty Surfboard RS-401', 'Diseño deportivo con protección UV400 para actividades al aire libre', 45000, $collectionId, [
            'frame_size' => 'grande', 'material' => 'tr90', 'shape' => 'deportivo',
            'gender' => 'unisex', 'frame_width' => 145, 'lens_height' => 48,
            'lens_width' => 60, 'bridge_width' => 16, 'temple_length' => 130,
            'rim_type' => 'semi-llanta',
        ], images: ['sol-3a.jpg', 'sol-3b.jpg'], colors: ['negro-mate', 'azul', 'rojo']);

        $this->createProduct($productTypeId, $vogue, 'VO5051S', 'Vogue VO5051S', 'Elegante diseño cat-eye en acetato, estilo femenino moderno', 55000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'ojo-de-gato',
            'gender' => 'femenino', 'frame_width' => 138, 'lens_height' => 44,
            'lens_width' => 54, 'bridge_width' => 16, 'temple_length' => 140,
            'rim_type' => 'llanta-completa',
        ], images: ['sol-1a.jpg', 'sol-2a.jpg'], colors: ['marron-havana', 'negro', 'borgona']);

        $this->createProduct($productTypeId, $vulk, 'VK-ROUND', 'Vulk VK-Round Solar', 'Marco redondo retro en acetato con lentes espejadas degradé', 49000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'redondo',
            'gender' => 'unisex', 'frame_width' => 136, 'lens_height' => 46,
            'lens_width' => 48, 'bridge_width' => 20, 'temple_length' => 140,
            'rim_type' => 'llanta-completa',
        ], images: ['sol-3a.jpg', 'sol-1b.jpg'], colors: ['negro', 'marron-havana', 'verde']);

        $this->createProduct($productTypeId, $vogue, 'VO5338S', 'Vogue VO5338S Mariposa', 'Diseño mariposa oversized, tendencia que fusiona elegancia y actitud', 62000, $collectionId, [
            'frame_size' => 'grande', 'material' => 'acetato', 'shape' => 'ojo-de-gato',
            'gender' => 'femenino', 'frame_width' => 144, 'lens_height' => 50,
            'lens_width' => 58, 'bridge_width' => 17, 'temple_length' => 145,
            'rim_type' => 'llanta-completa',
        ], images: ['sol-2a.jpg', 'sol-2b.jpg'], colors: ['lila', 'negro', 'rosa']);
    }

    private function prescriptionFramesSeeding(): void
    {
        $productTypeId = ProductType::where('name', 'Armazones')->value('id');
        $rayBan = Brand::where('name', 'Ray-Ban')->value('id');
        $vogue = Brand::where('name', 'Vogue')->value('id');
        $vulk = Brand::where('name', 'Vulk')->value('id');
        $collectionId = Collection::whereJsonContains('attribute_data->name->value', 'Armazones de Receta')->value('id') ?? 5;

        $this->createProduct($productTypeId, $rayBan, 'RX5228', 'Ray-Ban RX5228', 'Armazón cuadrado clásico en acetato para receta oftálmica', 62000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'cuadrado',
            'gender' => 'unisex', 'frame_weight' => 28, 'frame_width' => 140,
            'lens_height' => 42, 'lens_width' => 52, 'bridge_width' => 18,
            'temple_length' => 145, 'rim_type' => 'llanta-completa',
        ], images: ['frame-1a.jpg'], colors: ['negro', 'marron-havana', 'transparente']);

        $this->createProduct($productTypeId, $vogue, 'VO5184', 'Vogue VO5184', 'Armazón rectangular femenino en acetato, corte sofisticado', 48000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'acetato', 'shape' => 'rectangular',
            'gender' => 'femenino', 'frame_weight' => 22, 'frame_width' => 134,
            'lens_height' => 38, 'lens_width' => 50, 'bridge_width' => 17,
            'temple_length' => 140, 'rim_type' => 'llanta-completa',
        ], images: ['frame-2a.jpg', 'frame-2b.jpg'], detailBanner: 'mid-banner-1.webp', colors: ['borgona', 'negro', 'transparente']);

        $this->createProduct($productTypeId, $vulk, 'VKCLASS', 'Vulk VK-Classic', 'Armazón redondo en metal, estilo vintage retro atemporal', 35000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'metal', 'shape' => 'redondo',
            'gender' => 'unisex', 'frame_weight' => 18, 'frame_width' => 136,
            'lens_height' => 44, 'lens_width' => 46, 'bridge_width' => 19,
            'temple_length' => 140, 'rim_type' => 'llanta-completa',
        ], images: ['frame-1a.jpg'], colors: ['dorado', 'plateado', 'negro']);

        $this->createProduct($productTypeId, $vulk, 'VKMETRO', 'Vulk VK-Metro', 'Armazón cat-eye en acetato con detalles metálicos, diseño femenino', 38000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'acetato', 'shape' => 'ojo-de-gato',
            'gender' => 'femenino', 'frame_weight' => 24, 'frame_width' => 138,
            'lens_height' => 40, 'lens_width' => 52, 'bridge_width' => 17,
            'temple_length' => 142, 'rim_type' => 'llanta-completa',
        ], images: ['frame-2a.jpg', 'frame-2b.jpg'], colors: ['transparente', 'negro', 'lila']);

        $this->createProduct($productTypeId, $rayBan, 'RX7047', 'Ray-Ban RX7047', 'Armazón rectangular en TR90 ultraliviano, ideal para uso prolongado', 55000, $collectionId, [
            'frame_size' => 'grande', 'material' => 'tr90', 'shape' => 'rectangular',
            'gender' => 'masculino', 'frame_weight' => 15, 'frame_width' => 144,
            'lens_height' => 40, 'lens_width' => 54, 'bridge_width' => 18,
            'temple_length' => 145, 'rim_type' => 'llanta-completa',
        ], images: ['frame-1a.jpg'], colors: ['negro', 'marron-havana', 'gris']);

        $this->createProduct($productTypeId, $vogue, 'VO5371', 'Vogue VO5371', 'Armazón ovalado femenino en acetato con tono pastel y detalles dorados', 42000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'acetato', 'shape' => 'ovalado',
            'gender' => 'femenino', 'frame_weight' => 20, 'frame_width' => 132,
            'lens_height' => 36, 'lens_width' => 48, 'bridge_width' => 16,
            'temple_length' => 138, 'rim_type' => 'llanta-completa',
        ], images: ['frame-2a.jpg', 'frame-2b.jpg'], detailBanner: 'mid-banner-1.webp', colors: ['beige', 'rosa', 'transparente']);

        $this->createProduct($productTypeId, $vulk, 'VKURBAN', 'Vulk VK-Urban', 'Armazón cuadrado oversized en acetato, estilo urbano contemporáneo', 40000, $collectionId, [
            'frame_size' => 'grande', 'material' => 'acetato', 'shape' => 'cuadrado',
            'gender' => 'unisex', 'frame_weight' => 26, 'frame_width' => 146,
            'lens_height' => 48, 'lens_width' => 56, 'bridge_width' => 20,
            'temple_length' => 148, 'rim_type' => 'llanta-completa',
        ], images: ['frame-1a.jpg'], colors: ['negro', 'dorado', 'gris']);
    }

    private function kidsFramesSeeding(): void
    {
        $productTypeId = ProductType::where('name', 'Armazones')->value('id');
        $rusty = Brand::where('name', 'Rusty')->value('id');
        $vogue = Brand::where('name', 'Vogue')->value('id');
        $rayBan = Brand::where('name', 'Ray-Ban')->value('id');
        $vulk = Brand::where('name', 'Vulk')->value('id');
        $collectionId = Collection::whereJsonContains('attribute_data->name->value', 'Niños')->value('id') ?? 4;

        $this->createProduct($productTypeId, $rusty, 'RKFUN', 'Rusty Kids RK-Fun', 'Armazón flexible y resistente en TR90 para niños activos', 28000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'tr90', 'shape' => 'rectangular',
            'gender' => 'ninos', 'frame_weight' => 14, 'frame_width' => 120,
            'lens_height' => 34, 'lens_width' => 46, 'bridge_width' => 14,
            'temple_length' => 125, 'rim_type' => 'llanta-completa',
        ], images: ['kids-1a.jpg', 'kids-1b.jpg'], colors: ['azul', 'rojo', 'verde']);

        $this->createProduct($productTypeId, $vogue, 'VJSTAR', 'Vogue Junior VJ-Star', 'Diseño cat-eye colorido para niñas, liviano y seguro', 32000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'acetato', 'shape' => 'ojo-de-gato',
            'gender' => 'ninos', 'frame_weight' => 16, 'frame_width' => 118,
            'lens_height' => 32, 'lens_width' => 44, 'bridge_width' => 13,
            'temple_length' => 120, 'rim_type' => 'llanta-completa',
        ], images: ['kids-2a.jpg', 'kids-2b.jpg'], detailBanner: 'mid-banner-0.webp', colors: ['rosa', 'lila', 'azul']);

        $this->createProduct($productTypeId, $rayBan, 'RJ9050S', 'Ray-Ban Junior RJ9050S', 'Lente de sol infantil con protección UV400', 42000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'nylon', 'shape' => 'wayfarer',
            'gender' => 'ninos', 'frame_weight' => 18, 'frame_width' => 122,
            'lens_height' => 36, 'lens_width' => 48, 'bridge_width' => 15,
            'temple_length' => 125, 'rim_type' => 'llanta-completa',
        ], images: ['kids-1a.jpg', 'kids-2a.jpg'], colors: ['negro', 'azul']);

        $this->createProduct($productTypeId, $vulk, 'VKFLEX', 'Vulk VK-Flex Kids', 'Armazón con bisagras flexibles de memoria, anticaída para niños', 24000, $collectionId, [
            'frame_size' => 'pequena', 'material' => 'tr90', 'shape' => 'rectangular',
            'gender' => 'ninos', 'frame_weight' => 12, 'frame_width' => 116,
            'lens_height' => 30, 'lens_width' => 44, 'bridge_width' => 13,
            'temple_length' => 118, 'rim_type' => 'llanta-completa',
        ], images: ['kids-1a.jpg', 'kids-2b.jpg'], colors: ['naranja', 'verde', 'rosa']);
    }

    private function clipOnSeeding(): void
    {
        $productTypeId = ProductType::where('name', 'Armazones')->value('id');
        $vulk = Brand::where('name', 'Vulk')->value('id');
        $collectionId = Collection::whereJsonContains('attribute_data->name->value', 'Armazones Clip On')->value('id') ?? 6;

        $this->createProduct($productTypeId, $vulk, 'VCO01', 'Vulk Clip-On VCO-01', 'Clip-on magnético sin llanta, convierte tu armazón de receta en solar', 25000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'metal', 'shape' => 'rectangular',
            'gender' => 'unisex', 'frame_weight' => 8, 'frame_width' => 138,
            'lens_height' => 40, 'lens_width' => 50, 'rim_type' => 'sin-llanta',
        ], images: ['frame-1a.jpg'], colors: ['negro', 'gris']);

        $this->createProduct($productTypeId, $vulk, 'VCO02', 'Vulk Clip-On VCO-02', 'Clip-on semi llanta con bisagra doble para mayor estabilidad', 28000, $collectionId, [
            'frame_size' => 'grande', 'material' => 'metal', 'shape' => 'cuadrado',
            'gender' => 'unisex', 'frame_weight' => 10, 'frame_width' => 142,
            'lens_height' => 42, 'lens_width' => 54, 'rim_type' => 'semi-llanta',
        ], images: ['frame-2a.jpg', 'frame-2b.jpg'], detailBanner: 'mid-banner-1.webp', colors: ['plateado', 'dorado']);

        $this->createProduct($productTypeId, $vulk, 'VCO03', 'Vulk Clip-On VCO-03 Redondo', 'Clip-on redondo polarizado para armazones vintage y retro', 30000, $collectionId, [
            'frame_size' => 'mediana', 'material' => 'metal', 'shape' => 'redondo',
            'gender' => 'unisex', 'frame_weight' => 7, 'frame_width' => 134,
            'lens_height' => 44, 'lens_width' => 46, 'rim_type' => 'sin-llanta',
        ], images: ['frame-1a.jpg'], colors: ['dorado', 'negro']);
    }

    private function featuredAndOffersSeeding(): void
    {
        $featured = Collection::whereJsonContains('attribute_data->name->value', 'Destacados')->first();
        $offers = Collection::whereJsonContains('attribute_data->name->value', 'Ofertas')->first();

        foreach (['RB3025', 'VO5051S', 'RX5228', 'VKCLASS', 'RKFUN', 'VK-ROUND'] as $sku) {
            $productId = $this->productsByBaseSku[$sku] ?? null;
            if ($productId && $featured) {
                Product::find($productId)?->collections()->attach($featured->id);
            }
        }

        $offerSkus = [
            'RB2140' => 9500000,
            'VO5184' => 5800000,
            'VJSTAR' => 4200000,
            'VCO02' => 3500000,
            'VO5338S' => 7500000,
            'RX7047' => 7000000,
        ];

        foreach ($offerSkus as $sku => $comparePriceRaw) {
            $productId = $this->productsByBaseSku[$sku] ?? null;
            if (!$productId) {
                continue;
            }

            $product = Product::with('variants.prices')->find($productId);
            $product?->variants->each(fn ($v) => $v->prices()->update(['compare_price' => $comparePriceRaw]));

            if ($offers) {
                $product?->collections()->attach($offers->id);
            }
        }
    }

    /**
     * @param  array<string, string|int|float>  $frameAttrs
     * @param  array<int, string>  $images
     * @param  array<int, string>  $colors  Color handles e.g. ['negro', 'dorado']
     */
    private function createProduct(
        int $productTypeId,
        ?int $brandId,
        string $baseSku,
        string $name,
        string $description,
        int $priceArs,
        int $collectionId,
        array $frameAttrs = [],
        int $stock = 10,
        array $images = [],
        ?string $detailBanner = null,
        array $colors = [],
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

        $this->productsByBaseSku[$baseSku] = $product->id;

        if (empty($colors)) {
            $this->createVariant($product, $baseSku, $taxClass->id, $currency->id, $priceArs, $stock);
        } else {
            // Attach the Color option to the product so Filament shows the variant selector
            if ($this->colorOption) {
                $product->productOptions()->attach($this->colorOption->id, ['position' => 1]);
            }

            foreach ($colors as $i => $colorHandle) {
                // First color keeps the base SKU for backward-compat (LensConfigSeeder lookup)
                $sku = $i === 0
                    ? $baseSku
                    : $baseSku.'-'.strtoupper(str_replace('-', '', $colorHandle));

                $variant = $this->createVariant($product, $sku, $taxClass->id, $currency->id, $priceArs, $stock);

                if (isset($this->colorValues[$colorHandle])) {
                    $variant->values()->attach($this->colorValues[$colorHandle]->id);
                }
            }
        }

        $product->collections()->attach($collectionId);

        foreach ($images as $image) {
            $product->addMedia(database_path("seeders/assets/banners/frames/{$image}"))
                ->preservingOriginal()
                ->toMediaCollection('images');
        }

        if ($detailBanner) {
            $media = $product->addMedia(database_path("seeders/assets/banners/{$detailBanner}"))
                ->preservingOriginal()
                ->toMediaCollection('detail_banner');

            $data = $product->attribute_data;
            $data->put('detail_banner', new File($media->getUrl()));
            $product->attribute_data = $data;
            $product->save();
        }
    }

    private function createVariant(Product $product, string $sku, int $taxClassId, int $currencyId, int $priceArs, int $stock): ProductVariant
    {
        $variant = $product->variants()->create([
            'tax_class_id' => $taxClassId,
            'sku' => $sku,
            'stock' => $stock,
            'backorder' => 0,
            'purchasable' => 'in_stock_or_on_backorder',
        ]);

        $variant->prices()->create([
            'currency_id' => $currencyId,
            'price' => $priceArs * 100,
            'min_quantity' => 1,
        ]);

        return $variant;
    }
}
