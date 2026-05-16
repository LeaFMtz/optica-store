<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\LensType;
use App\Models\LensUse;
use App\Models\PrescriptionField;
use App\Models\PrescriptionType;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Text;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Brand;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\ProductType;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxZone;

class BasicConfig extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->basicConfig();

        $this->taxSeeding();

        $this->productTypeSeeding();

        $this->lensTypeSeeding();

        $this->prescriptionFieldSeeding();

        $this->bannerSeedings();

        $this->attributeGroupSeeding();

        $this->collectionsSeeding();

        $this->brandSeeding();
    }

    private function basicConfig(): void
    {
        Currency::create([
            'name' => 'Pesos',
            'code' => 'ARS',
            'exchange_rate' => 1,
            'decimal_places' => 2,
            'enabled' => true,
            'default' => true,
            'sync_prices' => true,
        ]);

        Language::create([
            'code' => 'es',
            'name' => 'Español',
            'default' => true,
        ]);

        Channel::create([
            'name' => 'Web',
            'handle' => 'web',
            'default' => true,
        ]);
    }

    private function taxSeeding(): void
    {
        $taxClassIva21 = TaxClass::create([
            'name' => 'IVA 21%',
            'default' => true,
        ]);

        $taxZone = TaxZone::create([
            'name' => 'Argentina',
            'zone_type' => 'country',
            'price_display' => 'tax_inclusive',
            'active' => true,
            'default' => true,
        ]);

        $argentina = Country::where('iso2', 'AR')->first();

        $taxZone->countries()->create([
            'country_id' => $argentina?->id,
        ]);

        $taxRate = $taxZone->taxRates()->create([
            'priority' => 1,
            'name' => 'IVA 21%',
        ]);

        $taxRate->taxRateAmounts()->create([
            'percentage' => 21,
            'tax_class_id' => $taxClassIva21->id,
        ]);
    }

    private function productTypeSeeding(): void
    {
        ProductType::create(['name' => 'Armazones']);
        ProductType::create(['name' => 'Lentes Compuestos']);
    }

    private function lensTypeSeeding(): void
    {
        $prescriptionTypes = [
            ['name' => 'Receta Lentes Disntacia'],
            ['name' => 'Recetas Lentes Progresivos'],
            ['name' => 'Recetas Lentes Bifocales'],
            ['name' => 'Receta Lentes de Lectura'],
        ];

        $pts = [];
        foreach ($prescriptionTypes as $data) {
            $pts[] = PrescriptionType::create($data);
        }

        [$ptDistancia, $ptProgresivo, $ptBifocales, $ptLectura] = $pts;

        $lensTypes = [];
        foreach ([
            ['name' => 'Fotocromatico', 'handle' => 'fotocromatico-simple', 'description' => 'Fotocromatico simple', 'sort_order' => 1],
            ['name' => 'Gafas polarizadas', 'handle' => 'gafas-polarizadas', 'description' => '', 'sort_order' => 2],
            ['name' => 'Teñido De Color', 'handle' => 'tenido-de-color', 'description' => '', 'sort_order' => 0],
            ['name' => 'Bloqueo de Luz Azul', 'handle' => 'bloqueo-de-luz-azul', 'description' => '', 'sort_order' => 0],
            ['name' => 'Transparente', 'handle' => 'transparente', 'description' => '', 'sort_order' => 0],
            ['name' => 'Fotocromático&Bloqueo de luz azul', 'handle' => 'fotocromaticobloqueo-de-luz-azul', 'description' => '', 'sort_order' => 0],
            ['name' => 'Fotocromático&visión nocturna', 'handle' => 'fotocromaticovision-nocturna', 'description' => '', 'sort_order' => 0],
        ] as $data) {
            $lensTypes[$data['handle']] = LensType::create($data);
        }

        $lensUses = [];
        foreach ([
            ['name' => 'Distancia', 'description' => 'Lentes de distancias bla bla', 'sort_order' => 1, 'prescription_type_id' => $ptDistancia->id],
            ['name' => 'Lectura', 'description' => 'Lentes de lectura y bla bla', 'sort_order' => 2, 'prescription_type_id' => $ptLectura->id],
            ['name' => 'Bifocales', 'description' => 'Lentes bifocales y bla bla', 'sort_order' => 3, 'prescription_type_id' => $ptBifocales->id],
            ['name' => 'Progresivo', 'description' => '', 'sort_order' => 2, 'prescription_type_id' => $ptProgresivo->id],
            ['name' => 'Lentes Funcionales', 'description' => '', 'sort_order' => 0, 'prescription_type_id' => null],
        ] as $data) {
            $lensUses[$data['name']] = LensUse::create($data);
        }

        // Valid LensType <-> LensUse pivot combinations
        $pivot = [
            'fotocromatico-simple' => ['Distancia', 'Progresivo'],
            'gafas-polarizadas' => ['Distancia', 'Progresivo'],
            'tenido-de-color' => ['Bifocales'],
            'bloqueo-de-luz-azul' => ['Lectura', 'Lentes Funcionales'],
            'transparente' => ['Lectura', 'Lentes Funcionales'],
            'fotocromaticobloqueo-de-luz-azul' => ['Lentes Funcionales'],
            'fotocromaticovision-nocturna' => ['Lentes Funcionales'],
        ];

        foreach ($pivot as $lensTypeHandle => $lensUseNames) {
            foreach ($lensUseNames as $lensUseName) {
                $lensTypes[$lensTypeHandle]->lensUses()->attach($lensUses[$lensUseName]->id);
            }
        }
    }

    private function prescriptionFieldSeeding(): void
    {
        $fields = [];
        foreach ([
            ['key' => 'esfera',            'label' => 'Esfera',             'min' => -20.00, 'max' => 20.00, 'step' => 0.25, 'sort_order' => 1],
            ['key' => 'cilindro',          'label' => 'Cilindro',           'min' => -8.00,  'max' => 8.00,  'step' => 0.25, 'sort_order' => 2],
            ['key' => 'eje',               'label' => 'Eje',                'min' => 0,      'max' => 180,   'step' => 1.00, 'sort_order' => 3],
            ['key' => 'add',               'label' => 'Add',                'min' => 0.75,   'max' => 4.00,  'step' => 0.25, 'sort_order' => 4],
            ['key' => 'distancia_pupilar', 'label' => 'Distancia Pupilar',  'min' => 50.00,  'max' => 80.00, 'step' => 0.50, 'sort_order' => 5],
        ] as $data) {
            $fields[$data['key']] = PrescriptionField::create($data);
        }

        // Each type attaches its fields in clinical order with sort_order on the pivot
        $typeFields = [
            'Receta Lentes Disntacia' => ['esfera', 'cilindro', 'eje', 'distancia_pupilar'],
            'Receta Lentes de Lectura' => ['esfera', 'cilindro', 'eje', 'distancia_pupilar'],
            'Recetas Lentes Bifocales' => ['esfera', 'cilindro', 'eje', 'add', 'distancia_pupilar'],
            'Recetas Lentes Progresivos' => ['esfera', 'cilindro', 'eje', 'add', 'distancia_pupilar'],
        ];

        foreach ($typeFields as $typeName => $fieldKeys) {
            $type = PrescriptionType::where('name', $typeName)->first();
            foreach ($fieldKeys as $order => $key) {
                $type?->prescriptionFields()->attach($fields[$key]->id, ['sort_order' => $order + 1]);
            }
        }
    }

    private function brandSeeding(): void
    {
        $brands = [
            [
                'name' => 'Ray-Ban',
                'attribute_data' => [],
            ],
            [
                'name' => 'Rusty',
                'attribute_data' => [],
            ],
            [
                'name' => 'Vogue',
                'attribute_data' => [],
            ],
            [
                'name' => 'Vulk',
                'attribute_data' => [],
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }

    private function attributeGroupSeeding(): void
    {
        $collectionGroup = AttributeGroup::create([
            'attributable_type' => 'collection',
            'name' => ['es' => 'Información Básica'],
            'handle' => 'informacion-basica-collection',
            'position' => 1,
        ]);

        $productGroup = AttributeGroup::create([
            'attributable_type' => 'product',
            'name' => ['es' => 'Información Básica'],
            'handle' => 'informacion-basica-product',
            'position' => 1,
        ]);

        $attributes = $this->getBasicDataAttributesArray();

        foreach ($attributes as $attr) {
            $collectionGroup->attributes()->create(array_merge($attr, ['attribute_type' => 'collection']));
            $productGroup->attributes()->create(array_merge($attr, ['attribute_type' => 'product']));
        }
    }

    private function collectionsSeeding(): void
    {
        $group = CollectionGroup::create([
            'name' => 'Catálogo',
            'handle' => 'catalog',
        ]);

        $categories = [
            ['name' => 'Destacados',          'handle' => 'destacados'],
            ['name' => 'Ofertas',             'handle' => 'ofertas'],
            ['name' => 'Lentes de Sol',       'handle' => 'lentes-de-sol'],
            ['name' => 'Niños',               'handle' => 'ninos'],
            ['name' => 'Armazones de Receta', 'handle' => 'armazones-de-receta'],
            ['name' => 'Armazones Clip On',   'handle' => 'armazones-clip-on'],
        ];

        foreach ($categories as $category) {
            Collection::create([
                'collection_group_id' => $group->id,
                'parent_id' => null,
                'attribute_data' => [
                    'name' => new Text($category['name']),
                ],
                'sort' => 'custom',
                'type' => 'static',
            ]);
        }
    }

    private function getBasicDataAttributesArray(): array
    {
        return [
            [
                'position' => 1,
                'name' => ['es' => 'Nombre'],
                'description' => ['es' => 'Nombre del elemento'],
                'handle' => 'name',
                'type' => Text::class,
                'required' => true,
                'searchable' => true,
                'filterable' => true,
                'system' => false,
                'configuration' => ['richtext' => false],
            ],
            [
                'position' => 2,
                'name' => ['es' => 'Descripción'],
                'description' => ['es' => 'Descripción detallada'],
                'handle' => 'description',
                'type' => Text::class,
                'required' => false,
                'searchable' => true,
                'filterable' => false,
                'system' => false,
                'configuration' => ['richtext' => true],
            ],
        ];
    }

    private function bannerSeedings(): void
    {
        $banners = [
            [
                'title' => 'Hero banner primario',
                'position' => 'home_hero',
                'order' => 1,
                'image' => 'slide-hero-1.webp',
                'mobile' => 'slide-hero-1-mobile.webp',
            ],
            [
                'title' => 'Hero banner secundario',
                'position' => 'home_hero',
                'order' => 2,
                'image' => 'slide-hero-2.webp',
                'mobile' => 'slide-hero-2-mobile.webp',
            ],
            [
                'title' => 'Mid banner primario',
                'position' => 'home_middle',
                'order' => 1,
                'image' => 'mid-banner-0.webp',
                'mobile' => null,
            ],
            [
                'title' => 'Mid banner secundario',
                'position' => 'home_middle',
                'order' => 2,
                'image' => 'mid-banner-1.webp',
                'mobile' => null,
            ],
            [
                'title' => 'Newsletter banner',
                'position' => 'home_newsletter',
                'order' => 1,
                'image' => 'newsletter-banner.webp',
                'mobile' => 'newsletter-banner-mobile.webp',
            ],
        ];

        foreach ($banners as $data) {
            $banner = Banner::create(collect($data)->except(['image', 'mobile'])->toArray());

            if (isset($data['image'])) {
                $banner->addMedia(database_path("seeders/assets/banners/{$data['image']}"))
                    ->preservingOriginal()
                    ->toMediaCollection('image');
            }

            if (isset($data['mobile']) and !empty($data['mobile'])) {
                $banner->addMedia(database_path("seeders/assets/banners/{$data['mobile']}"))
                    ->preservingOriginal()
                    ->toMediaCollection('mobile_image');
            }
        }
    }
}
