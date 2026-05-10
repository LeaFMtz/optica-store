<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-webhook-secret';

    /**
     * A request with a missing x-signature header is rejected with 400.
     */
    public function test_missing_signature_returns_400(): void
    {
        $response = $this->postJson('/webhooks/mercadopago?topic=payment&data_id=123');

        $response->assertStatus(400);
    }

    /**
     * A request with an invalid x-signature is rejected with 400.
     */
    public function test_invalid_signature_returns_400(): void
    {
        $response = $this->postJson(
            '/webhooks/mercadopago?topic=payment&data_id=123',
            [],
            ['x-signature' => 'ts=123456789,v1=invalidsignaturehash'],
        );

        $response->assertStatus(400);
    }

    /**
     * An unknown topic is accepted (200) without processing.
     */
    public function test_unknown_topic_returns_200(): void
    {
        $ts = (string) time();
        $dataId = '456';
        $signature = $this->buildSignature($dataId, $ts);

        $response = $this->postJson(
            "/webhooks/mercadopago?topic=merchant_order&data_id={$dataId}",
            [],
            ['x-signature' => $signature],
        );

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Ignored.']);
    }

    /**
     * A valid signature with payment topic is accepted (200).
     * The MP service is replaced with a stub to avoid real API calls.
     */
    public function test_valid_signature_payment_topic_returns_200(): void
    {
        $ts = (string) time();
        $dataId = '789';
        $signature = $this->buildSignature($dataId, $ts);

        // Stub MercadoPagoService to avoid real API calls
        $this->instance(MercadoPagoService::class, new class extends MercadoPagoService
        {
            public function getPayment(int $paymentId): array
            {
                return [
                    'id' => $paymentId,
                    'status' => 'approved',
                ];
            }
        });

        $response = $this->postJson(
            "/webhooks/mercadopago?topic=payment&data_id={$dataId}",
            [],
            ['x-signature' => $signature],
        );

        // No matching order in DB → still returns 200 (no-op, not an error)
        $response->assertStatus(200);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mercadopago.webhook_secret' => $this->secret]);
    }

    /**
     * Build a valid x-signature header value for a given data_id.
     * Note: MP uses "data.id" in the URL but PHP converts dots → underscores.
     * The middleware extracts data_id from the raw query string after the same normalization.
     */
    private function buildSignature(string $dataId, string $ts): string
    {
        $signedString = "ts:{$ts};v1:{$dataId}";
        $hash = hash_hmac('sha256', $signedString, $this->secret);

        return "ts={$ts},v1={$hash}";
    }
}
