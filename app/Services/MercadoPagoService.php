<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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
     * @param  string  $paymentTypeId  Payment type: credit_card or debit_card (from Brick onSubmit additionalData.paymentTypeId)
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
        string $paymentTypeId,
        string $email,
        int $installments,
        string $externalReference,
    ): array {
        $accessToken = $this->getAccessToken();

        $payload = [
            'type' => 'online',
            'processing_mode' => 'automatic',
            'capture_mode' => 'automatic',
            'external_reference' => $externalReference,
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
                            'type' => $paymentTypeId,
                            'token' => $token,
                            'installments' => $installments,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->withHeader('X-Idempotency-Key', hash('sha256', $externalReference))
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
     * Total refund of an order via the MercadoPago Orders API.
     *
     * POST /v1/orders/{orderId}/refund — empty body only.
     * The Orders API refund endpoint does NOT accept amount/transaction_id
     * parameters (reserved for "manual" processing_mode orders).
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException on network errors or API rejection
     */
    public function refundOrder(string $orderId): array
    {
        $accessToken = $this->getAccessToken();

        try {
            $response = Http::withToken($accessToken)
                ->withHeader('X-Idempotency-Key', hash('sha256', $orderId.':refund:total'))
                ->timeout(15)
                ->withBody('{}', 'application/json')
                ->post(self::API_BASE.'/v1/orders/'.$orderId.'/refund');

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
     * Partial refund via the MercadoPago Payments API.
     *
     * POST /v1/payments/{paymentId}/refunds
     * Used for partial refunds because the Orders API refund endpoint
     * does not accept per-transaction amount parameters in automatic mode.
     *
     * @param  string  $paymentId  The MP payment ID (e.g. "pay_01JC...")
     * @param  float  $amount  Amount to refund in currency units
     * @return array<string, mixed>
     *
     * @throws RuntimeException on network errors or API rejection
     */
    public function refundPayment(string $paymentId, float $amount): array
    {
        $accessToken = $this->getAccessToken();

        try {
            $response = Http::withToken($accessToken)
                ->withHeader('X-Idempotency-Key', hash('sha256', $paymentId.':refund:'.$amount))
                ->timeout(15)
                ->post(self::API_BASE.'/v1/payments/'.$paymentId.'/refunds', [
                    'amount' => $amount,
                ]);

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
     * Get the access token lazily from config.
     */
    private function getAccessToken(): string
    {
        return (string) config('services.mercadopago.access_token', '');
    }
}
