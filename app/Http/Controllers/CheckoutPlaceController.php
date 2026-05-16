<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\CreateZipnovaShipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\ProductVariant;

class CheckoutPlaceController extends Controller
{
    /**
     * Place the order using Lunar's CartSession::createOrder().
     * No payment processing — cash-in-hand / offline flow.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $cart = CartSession::current();

        if (!$cart) {
            return response()->json(['message' => 'No active cart.'], 422);
        }

        if (!$cart->shippingAddress || !$cart->billingAddress) {
            return response()->json(['message' => 'Address is required before placing the order.'], 422);
        }

        if (!$cart->shippingAddress->shipping_option) {
            $retloc = ShippingManifest::getOptions($cart)->first(
                fn ($o) => $o->getIdentifier() === 'RETLOC',
            );

            if (!$retloc) {
                return response()->json(['message' => 'No hay opción de retiro disponible.'], 422);
            }

            CartSession::setShippingOption($retloc);
            $cart->refresh();
        }

        foreach ($cart->lines as $line) {
            if ($line->purchasable_type !== 'product_variant') {
                continue;
            }

            $variant = ProductVariant::find($line->purchasable_id);

            if ($variant && !$variant->canBeFulfilledAtQuantity($line->quantity)) {
                return response()->json([
                    'message' => 'Uno o más productos ya no tienen stock suficiente para completar el pedido.',
                ], 422);
            }
        }

        // createOrder(forget: true) converts the cart into an order and clears the session.
        $order = CartSession::createOrder(forget: false);

        $pointId = session('zipnova_pending_point_id');
        if ($pointId !== null) {
            $meta = $order->meta ? $order->meta->toArray() : [];
            $meta['zipnova_point_id'] = $pointId;
            $order->meta = $meta;
            $order->save();
            session()->forget('zipnova_pending_point_id');
        }

        CreateZipnovaShipment::dispatch($order);

        CartSession::forget();

        return response()->json([
            'reference' => $order->reference,
            'order_id' => $order->id,
        ]);
    }
}
