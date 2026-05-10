<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * MercadoPago Orders API service using Laravel Http facade.
 *
 * Replaces mercadopago/dx-php SDK with direct HTTP calls to
 * POST /v1/orders and GET /v1/orders/{id}.
 */
class MercadoPagoService
{
    private const API_BASE = 'https://api.mercadopago.com';

    /**
     * Create a MercadoPago order with automatic processing.
     *
     * @param  float  $amount  Amount in currency units (NOT centavos)
     * @param  string  $token  Card token from mp.cardToken.create()
     * @param  string  $paymentMethodId  e.g. "visa"
     * @param  string  $issuerId  Bank issuer id
     * @param  string  $email  Payer email
     * @param  int  $installments  Number of installments
     * @param  string  $externalReference  Order reference for tracking
     * @return array<string, mixed> Raw MP Orders API response
     *
     * @throws RuntimeException on network errors or API rejection
     */
    public function createOrder(
        float $amount,
        string $token,
        string $paymentMethodId,
        string $issuerId,
        string $email,
        int $installments = 1,
        ?string $externalReference = null,
    ): array {
        $accessToken = $this->getAccessToken();

        $payload = [
            'type' => 'online',
            'processing_mode' => 'automatic',
            'capture_mode' => 'automatic',
            'external_reference' => $externalReference ?: 'order-'.Str::uuid(),
            'total_amount' => (string) number_format($amount, 2, '.', ''),
            'payer' => [
                'email' => $email,
            ],
            'transactions' => [
                'payments' => [
                    [
                        'amount' => (string) number_format($amount, 2, '.', ''),
                        'payment_method' => [
                            'id' => $paymentMethodId,
                            'type' => 'credit_card',
                            'token' => $token,
                            'installments' => $installments,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->withHeader('X-Idempotency-Key', (string) Str::uuid())
                ->timeout(15)
                ->post(self::API_BASE.'/v1/orders', $payload);

            if ($response->failed()) {
                throw new RuntimeException(
                    'MercadoPago API error: '.$response->status().' - '.$response->body(),
                    $response->status(),
                );
            }

            return $response->json();
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'MercadoPago connection error: '.$e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Retrieve an order by ID from the MercadoPago Orders API.
     *
     * @param  string  $orderId  The MP order ID
     * @return array<string, mixed>
     *
     * @throws RuntimeException on network errors
     */
    public function getOrder(string $orderId): array
    {
        $accessToken = $this->getAccessToken();

        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->get(self::API_BASE."/v1/orders/{$orderId}");

            if ($response->failed()) {
                throw new RuntimeException(
                    'MercadoPago API error fetching order: '.$response->status(),
                    $response->status(),
                );
            }

            return $response->json();
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'MercadoPago connection error: '.$e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Check if Orders API mode is enabled via feature flag.
     */
    public function isOrdersModeEnabled(): bool
    {
        return config('services.mercadopago.api_mode', 'orders') === 'orders';
    }

    /**
     * Get the access token lazily from config.
     */
    private function getAccessToken(): string
    {
        return (string) config('services.mercadopago.access_token', '');
    }
}
