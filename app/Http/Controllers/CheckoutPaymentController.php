<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\CreateZipnovaShipment;
use App\PaymentTypes\MercadoPagoPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lunar\Facades\CartSession;
use Lunar\Facades\Payments;

class CheckoutPaymentController extends Controller
{
    /**
     * Process the MercadoPago payment and create the order on approval.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'payment_method_id' => ['required', 'string'],
            'payment_type_id' => ['nullable', 'string', 'in:credit_card,debit_card'],
            'installments' => ['nullable', 'integer', 'min:1'],
            'payer' => ['nullable', 'array'],
            'payer.email' => ['nullable', 'email'],
        ]);

        $cart = CartSession::current();

        if (!$cart) {
            return response()->json(['message' => 'No active cart.'], 422);
        }

        if (!$cart->shippingAddress || !$cart->billingAddress) {
            return response()->json(['message' => 'Se requiere una dirección antes de procesar el pago.'], 422);
        }

        if (!$cart->shippingAddress->shipping_option) {
            return response()->json(['message' => 'Debe seleccionar una opción de envío.'], 422);
        }

        // payer.email may come as nested (from frontend form) or as top-level email
        $payerEmail = $validated['payer']['email']
            ?? $request->input('email', '')
            ?? $cart->shippingAddress->contact_email
            ?? '';

        try {
            /** @var MercadoPagoPayment $driver */
            $driver = Payments::driver('mercadopago');

            // Ensure the cart has a stable external_reference for idempotency.
            // We store it in cart meta so retries reuse the same key, preventing
            // duplicate charges via MercadoPago's X-Idempotency-Key header.
            $cartMeta = $cart->meta ? $cart->meta->toArray() : [];
            if (empty($cartMeta['mp_external_reference'])) {
                $cartMeta['mp_external_reference'] = Str::uuid()->toString();
                $cart->meta = $cartMeta;
                $cart->save();
            }

            $result = $driver
                ->cart($cart)
                ->withData([
                    'token' => $validated['token'],
                    'payment_method_id' => $validated['payment_method_id'],
                    'payment_type_id' => $validated['payment_type_id'] ?? 'credit_card',
                    'installments' => $validated['installments'] ?? 1,
                    'payer_email' => $payerEmail,
                    'external_reference' => $cartMeta['mp_external_reference'],
                ])
                ->authorize();

            if (!$result->success) {
                return response()->json([
                    'message' => 'Pago rechazado. Verificá los datos de tu tarjeta.',
                    'status_detail' => $result->message,
                ], 422);
            }

            // Payment approved/accredited — create the order
            $order = CartSession::createOrder(forget: false);

            // Build meta as a plain array, then assign once
            $meta = $order->meta ? $order->meta->toArray() : [];
            $meta['mp_order_id'] = $driver->lastOrderId;

            $pointId = session('zipnova_pending_point_id');
            if ($pointId !== null) {
                $meta['zipnova_point_id'] = $pointId;
                session()->forget('zipnova_pending_point_id');
            }

            if ($result->message === 'accredited') {
                $meta['mp_status'] = 'accredited';
                $order->status = 'payment-received';
            } else {
                $meta['mp_status'] = 'processing';
            }

            $order->meta = $meta;
            $order->save();

            CreateZipnovaShipment::dispatch($order);

            // Create a payment transaction so Lunar shows the correct paid amount
            $order->transactions()->create([
                'success' => $result->message === 'accredited',
                'type' => 'capture',
                'driver' => 'mercadopago',
                'amount' => $cart->total->value,
                'reference' => $driver->lastOrderId,
                'status' => $result->message === 'accredited' ? 'settled' : 'pending',
                'card_type' => $validated['payment_type_id'] ?? 'credit_card',
                'meta' => [
                    'mp_order_id' => $driver->lastOrderId,
                    'mp_payment_id' => $driver->lastPaymentId,
                ],
            ]);

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
