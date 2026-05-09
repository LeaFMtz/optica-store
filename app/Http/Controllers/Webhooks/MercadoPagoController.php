<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Transaction;

class MercadoPagoController extends Controller
{
    /**
     * Handle MercadoPago IPN webhook notifications.
     *
     * Receives payment status updates asynchronously and syncs
     * the corresponding Lunar transaction and order status.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $topic = $request->input('topic');
        $resource = $request->input('resource');
        $dataId = $request->input('data.id');

        Log::info('MercadoPago webhook received', [
            'topic' => $topic,
            'resource' => $resource,
            'data_id' => $dataId,
        ]);

        if ($topic !== 'payment') {
            return response()->json(['message' => 'Ignored topic'], 200);
        }

        if (!$dataId) {
            return response()->json(['message' => 'Missing data.id'], 400);
        }

        // Find the transaction by the preference_id / payment_id stored during checkout
        $transaction = Transaction::where('type', 'intent')
            ->where('gateway', 'mercadopago')
            ->where('reference', $dataId)
            ->first();

        if (!$transaction) {
            Log::warning('MercadoPago webhook: no matching transaction found', [
                'data_id' => $dataId,
            ]);

            return response()->json(['message' => 'Transaction not found'], 200);
        }

        // Determine status from the webhook payload
        $action = $request->input('action');

        $statusMap = [
            'payment.created' => 'pending',
            'payment.approved' => 'paid',
            'payment.rejected' => 'rejected',
        ];

        $newStatus = $statusMap[$action] ?? null;

        if ($newStatus) {
            $transaction->update(['status' => $newStatus]);

            // Sync the order status
            $order = $transaction->order;

            if ($order) {
                $orderStatusMap = [
                    'pending' => 'payment-pending',
                    'paid' => 'payment-received',
                    'rejected' => 'payment-error',
                ];

                $order->update([
                    'status' => $orderStatusMap[$newStatus] ?? $order->status,
                ]);
            }
        }

        return response()->json(['message' => 'OK'], 200);
    }
}
