<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductLensConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $configurations = ProductLensConfiguration::with(['lensUse', 'lensType', 'lensQuality'])
            ->where('product_id', $productId)
            ->get();

        if ($configurations->isEmpty()) {
            return response()->json(['uses' => []]);
        }

        // Build uses → types → qualities tree from the configurations for this product.
        // Each quality entry under a type carries the configuration_id so the storefront
        // can POST the exact ProductLensConfiguration row to the cart.
        $usesById = [];

        foreach ($configurations as $config) {
            $useId = $config->lens_use_id;
            $typeId = $config->lens_type_id;

            if (!isset($usesById[$useId])) {
                $usesById[$useId] = [
                    'id' => $useId,
                    'name' => $config->lensUse->name,
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
                    'qualities' => [],
                ];
                $typeIndex = count($usesById[$useId]['types']) - 1;
            }

            $quality = $config->lensQuality;
            $finalPrice = $config->price_override ?? $quality->base_price;

            $usesById[$useId]['types'][$typeIndex]['qualities'][] = [
                'configuration_id' => $config->id,
                'id' => $quality->id,
                'name' => $quality->name,
                'description' => $quality->description,
                'features' => $quality->features,
                'base_price' => $quality->base_price,
                'is_recommended' => $quality->is_recommended,
                'final_price' => $finalPrice,
            ];
        }

        return response()->json([
            'uses' => array_values($usesById),
        ]);
    }
}
