<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\PaymentTypes\MercadoPagoPayment;
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
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'payment_method_id' => ['required', 'string'],
            'issuer_id' => ['nullable', 'string'],
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

            if (!$result->success) {
                return response()->json([
                    'message' => 'Pago rechazado. Verificá los datos de tu tarjeta.',
                    'status_detail' => $result->message,
                ], 422);
            }

            // Payment approved/accredited — create the order
            $order = CartSession::createOrder(forget: false);

            // Persist the MercadoPago order ID and status
            $existing = $order->meta ? (array) $order->meta : [];
            $order->meta = array_merge($existing, [
                'mp_order_id' => $driver->lastOrderId,
            ]);

            // Only set payment-received if truly accredited (not processing)
            if ($result->message === 'accredited') {
                $order->meta = array_merge($order->meta, ['mp_status' => 'accredited']);
                $order->status = 'payment-received';
            } else {
                // processing → leave as awaiting-payment, webhook will update
                $order->meta = array_merge($order->meta, ['mp_status' => 'processing']);
            }

            $order->save();

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
