<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Facades\ShippingManifest;

class CheckoutShippingController extends Controller
{
    /**
     * Select a shipping option for the current cart.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $cart = CartSession::current();

        if (!$cart) {
            return response()->json(['message' => 'No active cart.'], 422);
        }

        $option = ShippingManifest::getOptions($cart)->first(
            fn ($opt) => $opt->getIdentifier() === $validated['identifier'],
        );

        if (!$option) {
            return response()->json(['message' => 'Invalid shipping option.'], 422);
        }

        CartSession::setShippingOption($option);

        return response()->json(['message' => 'Shipping option selected.']);
    }
}
