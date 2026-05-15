<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\DataTypes\Price;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\CartSession;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\TaxClass;

class CheckoutShippingController extends Controller
{
    /**
     * Select a shipping option for the current cart.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'point_id' => ['nullable', 'integer'],
        ]);

        $cart = CartSession::current();

        if (!$cart) {
            return response()->json(['message' => 'No active cart.'], 422);
        }

        $identifier = $validated['identifier'];

        $option = ShippingManifest::getOptions($cart)->first(
            fn ($opt) => $opt->getIdentifier() === $identifier,
        );

        if (!$option && str_starts_with($identifier, 'ZN_')) {
            $zipnovaOptions = session('zipnova_quote_options', []);
            $data = $zipnovaOptions[$identifier] ?? null;

            if ($data) {
                if (($data['service_type_code'] ?? '') === 'pickup_point' && empty($validated['point_id'])) {
                    return response()->json(['message' => 'Debe seleccionar un punto de retiro.'], 422);
                }

                $isCollect = ($data['service_type_code'] ?? '') === 'pickup_point';

                $option = new ShippingOption(
                    name: $data['name'],
                    description: $data['name'],
                    identifier: $identifier,
                    price: new Price($data['price'] * 100, $cart->currency, 1),
                    taxClass: TaxClass::getDefault(),
                    collect: $isCollect,
                );
                ShippingManifest::addOption($option);

                if (!empty($validated['point_id'])) {
                    session(['zipnova_pending_point_id' => (int) $validated['point_id']]);
                } else {
                    session()->forget('zipnova_pending_point_id');
                }
            }
        }

        if (!$option) {
            return response()->json(['message' => 'Invalid shipping option.'], 422);
        }

        CartSession::setShippingOption($option);

        return response()->json(['message' => 'Shipping option selected.']);
    }
}
