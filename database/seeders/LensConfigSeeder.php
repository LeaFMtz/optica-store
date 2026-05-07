<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LensType;
use App\Models\LensUse;
use App\Models\Product;
use App\Models\ProductLensConfiguration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Lunar\FieldTypes\Text;
use Lunar\Models\Currency;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

class LensConfigSeeder extends Seeder
{
    public function run(): void
    {
        $this->crystalProductsSeeding();
        $this->lensConfigSeeding();
    }

    private function crystalProductsSeeding(): void
    {
        $productTypeId = ProductType::where('name', 'Lentes Compuestos')->value('id');
        $taxClass = TaxClass::first();
        $currency = Currency::where('default', true)->first();

        $crystals = [
            ['sku' => 'CE200', 'name' => 'Cristal Económico', 'price' => 15000, 'stock' => 0],
            ['sku' => 'CRISES', 'name' => 'Cristal Estándar', 'price' => 25000, 'stock' => 0],
            ['sku' => 'CMID01', 'name' => 'Cristal Mid', 'price' => 35000, 'stock' => 0],
        ];

        foreach ($crystals as $data) {
            $product = Product::create([
                'product_type_id' => $productTypeId,
                'status' => 'published',
                'attribute_data' => ['name' => new Text($data['name'])],
            ]);

            $variant = $product->variants()->create([
                'tax_class_id' => $taxClass->id,
                'sku' => $data['sku'],
                'stock' => $data['stock'],
                'backorder' => 1,
                'purchasable' => 'always',
            ]);

            $variant->prices()->create([
                'currency_id' => $currency->id,
                'price' => $data['price'] * 100,
                'min_quantity' => 1,
            ]);
        }
    }

    private function lensConfigSeeding(): void
    {
        /** @var Collection<string, int> $lensTypes */
        $lensTypes = LensType::pluck('id', 'handle');

        /** @var Collection<string, int> $lensUses */
        $lensUses = LensUse::pluck('id', 'name');

        $crystals = ProductVariant::whereIn('sku', ['CE200', 'CRISES', 'CMID01'])
            ->pluck('product_id', 'sku');

        $eco = $crystals['CE200'];
        $std = $crystals['CRISES'];
        $mid = $crystals['CMID01'];

        /*
         * All valid combinations from opt_lens_type_lens_use pivot, mapped to a crystal
         * by lens complexity: Eco → basic clear, Std → photochromic/tinted, Mid → polarized/premium.
         */
        $recetaConfigs = [
            ['fotocromatico-simple',             'Distancia',          $std],
            ['fotocromatico-simple',             'Progresivo',         $std],
            ['gafas-polarizadas',                'Distancia',          $mid],
            ['gafas-polarizadas',                'Progresivo',         $mid],
            ['transparente',                     'Lectura',            $eco],
            ['transparente',                     'Lentes Funcionales', $eco],
            ['bloqueo-de-luz-azul',              'Lectura',            $eco],
            ['bloqueo-de-luz-azul',              'Lentes Funcionales', $eco],
            ['tenido-de-color',                  'Bifocales',          $std],
            ['fotocromaticobloqueo-de-luz-azul', 'Lentes Funcionales', $mid],
            ['fotocromaticovision-nocturna',     'Lentes Funcionales', $mid],
        ];

        // All prescription frames get the full matrix
        foreach (['RX5228', 'VO5184', 'VKCLASS', 'VKMETRO'] as $sku) {
            $this->attachConfigs($this->productIdBySku($sku), $recetaConfigs, $lensTypes, $lensUses);
        }

        // Solar Ray-Ban: prescription solar options
        $this->attachConfigs($this->productIdBySku('RB3025'), [
            ['fotocromatico-simple', 'Distancia', $std],
            ['gafas-polarizadas',    'Distancia', $mid],
        ], $lensTypes, $lensUses);

        $this->attachConfigs($this->productIdBySku('RB2140'), [
            ['gafas-polarizadas', 'Distancia', $mid],
            ['tenido-de-color',   'Bifocales',  $std],
        ], $lensTypes, $lensUses);

        // Kids: functional lenses only
        $this->attachConfigs($this->productIdBySku('RKFUN'), [
            ['transparente',        'Lentes Funcionales', $eco],
            ['bloqueo-de-luz-azul', 'Lentes Funcionales', $eco],
        ], $lensTypes, $lensUses);

        $this->attachConfigs($this->productIdBySku('RJ9050S'), [
            ['gafas-polarizadas', 'Distancia', $mid],
        ], $lensTypes, $lensUses);

        // No lens configs: RS401, VO5051S, VJSTAR, VCO01, VCO02
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: int}>  $configs
     * @param  Collection<string, int>  $lensTypes
     * @param  Collection<string, int>  $lensUses
     */
    private function attachConfigs(
        ?int $productId,
        array $configs,
        Collection $lensTypes,
        Collection $lensUses,
    ): void {
        if (!$productId) {
            return;
        }

        foreach ($configs as [$lensTypeHandle, $lensUseName, $crystalProductId]) {
            $lensTypeId = $lensTypes[$lensTypeHandle] ?? null;
            $lensUseId = $lensUses[$lensUseName] ?? null;

            if (!$lensTypeId || !$lensUseId) {
                continue;
            }

            ProductLensConfiguration::create([
                'product_id' => $productId,
                'lens_type_id' => $lensTypeId,
                'lens_use_id' => $lensUseId,
                'crystal_product_id' => $crystalProductId,
                'price_override' => null,
            ]);
        }
    }

    private function productIdBySku(string $sku): ?int
    {
        return ProductVariant::where('sku', $sku)->value('product_id');
    }
}
