<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;

class CheckoutAddressController extends Controller
{
    /**
     * Save the shipping (and optionally billing) address for the current cart.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'line_one' => ['required', 'string', 'max:255'],
            'line_two' => ['nullable', 'string', 'max:255'],
            'line_three' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'shipping_is_billing' => ['boolean'],
        ]);

        $cart = CartSession::current();

        if (!$cart) {
            return response()->json(['message' => 'No active cart.'], 422);
        }

        $addressData = collect($validated)->except('shipping_is_billing')->all();

        $cart->setShippingAddress($addressData);

        $shippingIsBilling = (bool) ($validated['shipping_is_billing'] ?? true);

        if ($shippingIsBilling) {
            $cart->refresh();

            if ($billing = $cart->billingAddress) {
                $billing->fill($addressData);
                $cart->setBillingAddress($billing);
            } else {
                $cart->setBillingAddress($addressData);
            }
        }

        return response()->json(['message' => 'Address saved.']);
    }
}
