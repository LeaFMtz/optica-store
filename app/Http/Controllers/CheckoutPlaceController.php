<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;

class CheckoutPlaceController extends Controller
{
    /**
     * Place the order using Lunar's CartSession::createOrder().
     * No payment processing — cash-in-hand / offline flow.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $cart = CartSession::current();

        if (!$cart) {
            return response()->json(['message' => 'No active cart.'], 422);
        }

        if (!$cart->shippingAddress || !$cart->billingAddress) {
            return response()->json(['message' => 'Address is required before placing the order.'], 422);
        }

        if (!$cart->shippingAddress->shipping_option) {
            return response()->json(['message' => 'A shipping option must be selected.'], 422);
        }

        // createOrder(forget: true) converts the cart into an order and clears the session.
        $order = CartSession::createOrder(forget: false);

        CartSession::forget();

        return response()->json([
            'reference' => $order->reference,
            'order_id' => $order->id,
        ]);
    }
}
