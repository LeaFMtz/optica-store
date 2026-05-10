<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Models\Order;

class MercadoPagoController extends Controller
{
    public function __construct(private readonly MercadoPagoService $mpService) {}

    /**
     * Process a MercadoPago webhook notification.
     * Always returns 200 to prevent MP from retrying on business logic failures.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $topic = $request->query('topic') ?? $request->input('type', '');

        // Only process payment notifications
        if ($topic !== 'payment') {
            return response()->json(['message' => 'Ignored.'], 200);
        }

        // PHP converts dots to underscores in $_GET, so parse raw query string
        $rawQuery = $request->server('QUERY_STRING', '');
        parse_str(str_replace('.', '_', $rawQuery), $queryParams);
        $dataId = (int) ($queryParams['data_id'] ?? 0);

        if (!$dataId) {
            return response()->json(['message' => 'No data.id provided.'], 200);
        }

        try {
            $payment = $this->mpService->getPayment($dataId);
        } catch (\RuntimeException) {
            // Log and return 200 so MP doesn't retry infinitely
            return response()->json(['message' => 'Could not fetch payment.'], 200);
        }

        $status = $payment['status'] ?? null;
        $paymentId = $payment['id'] ?? $dataId;

        // Find Lunar order by mp_payment_id stored in meta
        $order = Order::query()
            ->whereRaw("JSON_EXTRACT(meta, '$.mp_payment_id') = ?", [$paymentId])
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 200);
        }

        $lunarStatus = match ($status) {
            'approved' => 'payment-received',
            'rejected', 'cancelled' => 'payment-offline',  // treat as offline/failed
            default => null,
        };

        if ($lunarStatus) {
            $meta = $order->meta ? $order->meta->toArray() : [];
            $meta['mp_payment_id'] = $paymentId;
            $meta['mp_status'] = $status;

            $order->update([
                'status' => $lunarStatus,
                'meta' => $meta,
            ]);
        }

        return response()->json(['message' => 'Processed.'], 200);
    }
}
