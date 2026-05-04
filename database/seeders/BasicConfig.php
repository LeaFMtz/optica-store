<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Text;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Brand;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Currency;
use Lunar\Models\Language;

class BasicConfig extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
            'name' => 'Retail',
            'handle' => 'retail',
            'default' => true,
        ]);

        $this->bannerSeedings();

        $this->attributeGroupSeeding();

        $this->collectionsSeeding();

        $this->brandSeeding();
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
            ['name' => 'Lentes de Sol', 'handle' => 'lentes-de-sol'],
            ['name' => 'Niños', 'handle' => 'ninos'],
            ['name' => 'Armazones de Receta', 'handle' => 'armazones-de-receta'],
            ['name' => 'Armazones Clip On', 'handle' => 'armazones-clip-on'],
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
