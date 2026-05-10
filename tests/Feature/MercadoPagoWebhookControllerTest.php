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
        $response = $this->postJson('/webhooks/mercadopago?topic=order&data_id=ORD-TEST');

        $response->assertStatus(400);
    }

    /**
     * A request with an invalid x-signature is rejected with 400.
     */
    public function test_invalid_signature_returns_400(): void
    {
        $response = $this->postJson(
            '/webhooks/mercadopago?topic=order&data_id=ORD-TEST',
            [],
            [
                'x-signature' => 'ts=123456789,v1=invalidsignaturehash',
                'x-request-id' => 'req-abc-123',
            ],
        );

        $response->assertStatus(400);
    }

    /**
     * An unknown topic is accepted (200) without processing.
     */
    public function test_unknown_topic_returns_200(): void
    {
        $ts = (string) time();
        $dataId = 'ORD-UH56Y7';
        $requestId = 'req-unknown-1';
        $signature = $this->buildSignature($dataId, $requestId, $ts);

        $response = $this->postJson(
            "/webhooks/mercadopago?topic=merchant_order&data_id={$dataId}",
            [],
            [
                'x-signature' => $signature,
                'x-request-id' => $requestId,
            ],
        );

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Ignored.']);
    }

    /**
     * A valid signature with order topic is accepted (200).
     * The MP service is replaced with a stub to avoid real API calls.
     */
    public function test_valid_signature_order_topic_returns_200(): void
    {
        $ts = (string) time();
        $dataId = 'ORD-7L89YQ';
        $requestId = 'req-webhook-789';
        $signature = $this->buildSignature($dataId, $requestId, $ts);

        // Stub MercadoPagoService to avoid real API calls
        $this->instance(MercadoPagoService::class, new class extends MercadoPagoService
        {
            public function getOrder(string $orderId): array
            {
                return [
                    'id' => $orderId,
                    'status' => 'processed',
                ];
            }
        });

        $response = $this->postJson(
            "/webhooks/mercadopago?topic=order&data_id={$dataId}",
            [],
            [
                'x-signature' => $signature,
                'x-request-id' => $requestId,
            ],
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
     * Build a valid x-signature header value for Orders API webhook.
     *
     * Signed string format: "id:{dataID};request-id:{xRequestId};ts:{ts};"
     * Note: data.id must be lowercase per MP Orders API docs.
     */
    private function buildSignature(string $dataId, string $requestId, string $ts): string
    {
        $signedString = sprintf('id:%s;request-id:%s;ts:%s;', strtolower($dataId), $requestId, $ts);
        $hash = hash_hmac('sha256', $signedString, $this->secret);

        return "ts={$ts},v1={$hash}";
    }
}
