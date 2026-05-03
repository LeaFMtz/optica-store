<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;
use Lunar\Facades\Payments;

class CheckoutPaymentController extends Controller
{
    /**
     * Process the MercadoPago payment and create the order on approval.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'payment_method_id' => ['required', 'string'],
            'issuer_id' => ['nullable', 'string'],
            'installments' => ['nullable', 'integer', 'min:1'],
            'payer' => ['nullable', 'array'],
            'payer.email' => ['nullable', 'email'],
        ]);

        $cart = CartSession::current();

        if (! $cart) {
            return response()->json(['message' => 'No active cart.'], 422);
        }

        if (! $cart->shippingAddress || ! $cart->billingAddress) {
            return response()->json(['message' => 'Se requiere una dirección antes de procesar el pago.'], 422);
        }

        if (! $cart->shippingAddress->shipping_option) {
            return response()->json(['message' => 'Debe seleccionar una opción de envío.'], 422);
        }

        $payerEmail = $validated['payer']['email']
            ?? $cart->shippingAddress->contact_email
            ?? $request->user()?->email
            ?? '';

        try {
            /** @var \App\PaymentTypes\MercadoPagoPayment $driver */
            $driver = Payments::driver('mercadopago');

            $result = $driver
                ->cart($cart)
                ->withData([
                    'token' => $validated['token'],
                    'payment_method_id' => $validated['payment_method_id'],
                    'issuer_id' => $validated['issuer_id'] ?? '',
                    'installments' => $validated['installments'] ?? 1,
                    'payer_email' => $payerEmail,
                ])
                ->authorize();

            if (! $result->success) {
                return response()->json([
                    'message' => 'Pago rechazado. Verificá los datos de tu tarjeta.',
                    'status_detail' => $result->message,
                ], 422);
            }

            // Payment approved — create the order
            $order = CartSession::createOrder(forget: false);

            // Persist the MercadoPago payment ID for webhook reconciliation
            if ($driver->lastPaymentId !== null) {
                $existing = $order->meta ? (array) $order->meta : [];
                $order->meta = array_merge($existing, [
                    'mp_payment_id' => $driver->lastPaymentId,
                ]);
                $order->save();
            }

            CartSession::forget();

            return response()->json([
                'reference' => $order->reference,
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Error al procesar el pago. Intentá de nuevo más tarde.',
            ], 500);
        }
    }
}
