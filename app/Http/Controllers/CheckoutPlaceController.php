<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\PaymentTypes\MercadoPagoPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Facades\Payments;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\ProductVariant;

class CheckoutPlaceController extends Controller
{
    /**
     * Place the order.
     *
     * For cash-in-hand: creates the order directly via CartSession.
     * For mercadopago: delegates to the MercadoPagoPayment driver, which
     * creates a Checkout Pro Preference and returns the init_point URL
     * so the frontend can redirect the buyer to MercadoPago.
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

            if ($variant && ! $variant->canBeFulfilledAtQuantity($line->quantity)) {
                return response()->json([
                    'message' => 'Uno o más productos ya no tienen stock suficiente para completar el pedido.',
                ], 422);
            }
        }

        $paymentType = $request->input('payment_type', 'cash-in-hand');

        if ($paymentType === 'mercadopago') {
            return $this->handleMercadoPago($cart);
        }

        return $this->handleCashInHand($cart);
    }

    /**
     * Process a MercadoPago Checkout Pro payment.
     *
     * The driver creates the order internally and returns the
     * MercadoPago init_point URL for the frontend to redirect to.
     */
    private function handleMercadoPago($cart): JsonResponse
    {
        /** @var MercadoPagoPayment $driver */
        $driver = Payments::driver('mercadopago');

        $authorize = $driver->cart($cart)->authorize();

        if ($authorize && $authorize->success) {
            CartSession::forget();

            return response()->json([
                'reference' => $authorize->orderId ? (string) $authorize->orderId : null,
                'order_id' => $authorize->orderId,
                'redirect_url' => $authorize->message,
            ]);
        }

        return response()->json([
            'message' => $authorize?->message ?? 'Error al procesar el pago con Mercado Pago.',
        ], 422);
    }

    /**
     * Process a cash-in-hand / offline order.
     */
    private function handleCashInHand($cart): JsonResponse
    {
        $order = CartSession::createOrder(forget: false);

        CartSession::forget();

        return response()->json([
            'reference' => $order->reference,
            'order_id' => $order->id,
        ]);
    }
}
