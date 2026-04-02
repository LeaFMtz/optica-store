<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Lunar\Models\Order;
use MercadoPago\Payment;
use MercadoPago\SDK;

class MercadoPagoController extends Controller
{
    public function handle(Request $request)
    {
        $topic = $request->input('topic');
        $resourceId = $request->input('id');

        if ($topic !== 'payment' || !$resourceId) {
            return response()->json(['status' => 'ignored']);
        }

        $accessToken = config('services.mercadopago.access_token');

        if (!$accessToken) {
            return response()->json(['error' => 'Not configured'], 400);
        }

        $payment = $this->getPaymentDetails($resourceId, $accessToken);

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $externalReference = $payment->external_reference ?? null;

        if (!$externalReference) {
            return response()->json(['error' => 'No external reference'], 400);
        }

        $order = Order::find($externalReference);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $transaction = $order->transactions()
            ->where('driver', 'mercadopago')
            ->where('type', 'intent')
            ->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $statusMap = [
            'approved' => 'succeeded',
            'pending' => 'pending',
            'in_process' => 'pending',
            'in_mediation' => 'pending',
            'rejected' => 'failed',
            'cancelled' => 'failed',
            'refunded' => 'refunded',
            'charged_back' => 'failed',
        ];

        $newStatus = $statusMap[$payment->status] ?? 'pending';

        $transaction->update([
            'status' => $newStatus,
            'success' => $newStatus === 'succeeded',
            'meta' => array_merge($transaction->meta ?? [], [
                'payment_id' => $payment->id,
                'payment_status' => $payment->status,
            ]),
        ]);

        if ($newStatus === 'succeeded') {
            $order->update(['status' => 'paid']);
        }

        return response()->json(['status' => 'processed']);
    }

    private function getPaymentDetails(string $paymentId, string $accessToken): ?object
    {
        $sdk = new SDK($accessToken);

        try {
            $payment = Payment::find_by_id($paymentId);

            return $payment;
        } catch (\Exception $e) {
            return null;
        }
    }
}
