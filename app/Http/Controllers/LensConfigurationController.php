<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductLensConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\Pricing;
use Lunar\Models\Product;

class LensConfigurationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $productId = (int) $request->query('product_id');

        $product = Product::find($productId);

        if ($product === null) {
            return response()->json([], 404);
        }

        $configurations = ProductLensConfiguration::with([
            'lensUse.prescriptionType.prescriptionFields',
            'lensType:id,name,handle',
            'crystalProduct.variants.basePrices.currency',
        ])
            ->where('product_id', $productId)
            ->get();

        if ($configurations->isEmpty()) {
            return response()->json(['uses' => []]);
        }

        $usesById = [];

        foreach ($configurations as $config) {
            $useId = $config->lens_use_id;
            $typeId = $config->lens_type_id;

            if (!isset($usesById[$useId])) {
                $prescriptionType = $config->lensUse->prescriptionType;

                $usesById[$useId] = [
                    'id' => $useId,
                    'name' => $config->lensUse->name,
                    'prescription_type' => $prescriptionType ? [
                        'id' => $prescriptionType->id,
                        'name' => $prescriptionType->name,
                        'requires_prescription' => $prescriptionType->requires_prescription,
                        'prescription_fields' => $prescriptionType->prescriptionFields->map(fn ($f) => [
                            'key' => $f->key,
                            'label' => $f->label,
                            'min' => $f->min,
                            'max' => $f->max,
                            'step' => $f->step,
                        ])->values()->all(),
                    ] : null,
                    'types' => [],
                ];
            }

            $typeIndex = null;
            foreach ($usesById[$useId]['types'] as $i => $t) {
                if ($t['id'] === $typeId) {
                    $typeIndex = $i;
                    break;
                }
            }

            if ($typeIndex === null) {
                $usesById[$useId]['types'][] = [
                    'id' => $typeId,
                    'name' => $config->lensType->name,
                    'handle' => $config->lensType->handle,
                    'crystals' => [],
                ];
                $typeIndex = count($usesById[$useId]['types']) - 1;
            }

            $crystal = $config->crystalProduct;
            $crystalVariant = $crystal?->variants->first();

            $effectivePrice = $config->price_override;

            if ($effectivePrice === null && $crystalVariant !== null) {
                $pricing = Pricing::for($crystalVariant)->get();
                $effectivePrice = $pricing->matched?->price?->value ?? 0;
            }

            $usesById[$useId]['types'][$typeIndex]['crystals'][] = [
                'configuration_id' => $config->id,
                'crystal_product_id' => $config->crystal_product_id,
                'crystal_variant_id' => $crystalVariant?->id,
                'name' => $crystal?->translateAttribute('name') ?? '—',
                'price_override' => $config->price_override,
                'effective_price' => $effectivePrice ?? 0,
            ];
        }

        return response()->json([
            'uses' => array_values($usesById),
        ]);
    }
}
