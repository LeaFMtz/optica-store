<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMercadoPagoSignature
{
    /**
     * Verify the MercadoPago x-signature header before processing webhooks.
     *
     * MP signature format: "ts=TIMESTAMP,v1=HASH"
     * Signed string: "ts:{ts};v1:{data_id}"
     * where data_id comes from the query param "data.id".
     */
    public function handle(Request $request, Closure $next): Response
    {
        $xSignature = $request->header('x-signature');

        if (! $xSignature) {
            abort(400, 'Missing x-signature header.');
        }

        // Parse "ts=TIMESTAMP,v1=HASH"
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $parts[trim($key)] = trim($value);
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            abort(400, 'Invalid x-signature format.');
        }

        $ts = $parts['ts'];
        $receivedHash = $parts['v1'];

        // data.id comes from query param (MP sends it as part of the notification URL).
        // PHP converts dots to underscores in query params, so we parse the raw query string.
        $dataId = $this->extractDataId($request);

        // Reconstruct the signed string per MP docs: "ts:{ts};v1:{data_id}"
        $signedString = "ts:{$ts};v1:{$dataId}";

        $secret = config('services.mercadopago.webhook_secret', '');
        $expectedHash = hash_hmac('sha256', $signedString, $secret);

        if (! hash_equals($expectedHash, $receivedHash)) {
            abort(400, 'Invalid webhook signature.');
        }

        return $next($request);
    }

    /**
     * Extract the data.id value from the raw query string.
     * PHP converts dots to underscores in $_GET, so we must parse the raw string.
     */
    private function extractDataId(Request $request): string
    {
        $rawQuery = $request->server('QUERY_STRING', '');
        parse_str(str_replace('.', '_', $rawQuery), $params);

        return (string) ($params['data_id'] ?? '');
    }
}
