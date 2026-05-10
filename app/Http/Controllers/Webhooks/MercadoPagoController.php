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

        // Only process order notifications (Orders API uses topic=order)
        if ($topic !== 'order') {
            return response()->json(['message' => 'Ignored.'], 200);
        }

        // PHP converts dots to underscores in $_GET, so parse raw query string
        $rawQuery = $request->server('QUERY_STRING', '');
        parse_str(str_replace('.', '_', $rawQuery), $queryParams);
        $dataId = (string) ($queryParams['data_id'] ?? '');

        if (!$dataId) {
            return response()->json(['message' => 'No data.id provided.'], 200);
        }

        // Orders API uses string IDs (alphanumeric), not integers like Payments API
        try {
            $order = $this->mpService->getOrder($dataId);
        } catch (\RuntimeException) {
            // Log and return 200 so MP doesn't retry infinitely
            return response()->json(['message' => 'Could not fetch order.'], 200);
        }

        $status = $order['status'] ?? null;
        $mpOrderId = $order['id'] ?? $dataId;

        // Find Lunar order by mp_order_id stored in meta
        $lunarOrder = Order::query()
            ->whereRaw("JSON_EXTRACT(meta, '$.mp_order_id') = ?", [$mpOrderId])
            ->first();

        if (!$lunarOrder) {
            return response()->json(['message' => 'Order not found.'], 200);
        }

        // Map Orders API status to Lunar status
        // processed → payment-received (approved)
        // failed/canceled/expired → payment-offline
        $lunarStatus = match ($status) {
            'processed' => 'payment-received',
            'failed', 'canceled', 'expired' => 'payment-offline',
            default => null,
        };

        if ($lunarStatus) {
            $meta = $lunarOrder->meta ? $lunarOrder->meta->toArray() : [];
            $meta['mp_order_id'] = $mpOrderId;
            $meta['mp_status'] = $status;

            $lunarOrder->update([
                'status' => $lunarStatus,
                'meta' => $meta,
            ]);
        }

        return response()->json(['message' => 'Processed.'], 200);
    }
}
