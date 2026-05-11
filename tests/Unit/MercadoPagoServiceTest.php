<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mercadopago.access_token' => 'test_token']);
    }

    public function test_create_order_uses_deterministic_idempotency_key_from_external_reference(): void
    {
        $capturedKeys = [];

        Http::fake([
            'api.mercadopago.com/v1/orders' => function ($request) use (&$capturedKeys) {
                $capturedKeys[] = $request->header('X-Idempotency-Key')[0] ?? null;

                return Http::response([
                    'id' => 'ORD-TEST-123',
                    'status' => 'processed',
                    'status_detail' => 'accredited',
                ], 200);
            },
        ]);

        $service = new MercadoPagoService();

        $service->createOrder(
            amount: 100.00,
            token: 'card-token-abc',
            paymentMethodId: 'visa',
            paymentTypeId: 'credit_card',
            email: 'test@example.com',
            installments: 1,
            externalReference: 'CART-ABC',
        );

        $service->createOrder(
            amount: 100.00,
            token: 'card-token-abc',
            paymentMethodId: 'visa',
            paymentTypeId: 'credit_card',
            email: 'test@example.com',
            installments: 1,
            externalReference: 'CART-ABC',
        );

        $this->assertCount(2, $capturedKeys);
        $this->assertEquals($capturedKeys[0], $capturedKeys[1]);
        $this->assertEquals(
            hash('sha256', 'CART-ABC'),
            $capturedKeys[0],
            'Idempotency key must be SHA-256 hash of externalReference'
        );
    }

    public function test_create_order_different_references_produce_different_idempotency_keys(): void
    {
        $capturedKeys = [];

        Http::fake([
            'api.mercadopago.com/v1/orders' => function ($request) use (&$capturedKeys) {
                $capturedKeys[] = $request->header('X-Idempotency-Key')[0] ?? null;

                return Http::response([
                    'id' => 'ORD-TEST-456',
                    'status' => 'processed',
                    'status_detail' => 'accredited',
                ], 200);
            },
        ]);

        $service = new MercadoPagoService();

        $service->createOrder(
            amount: 100.00,
            token: 'card-token-abc',
            paymentMethodId: 'visa',
            paymentTypeId: 'credit_card',
            email: 'test@example.com',
            installments: 1,
            externalReference: 'CART-ABC',
        );

        $service->createOrder(
            amount: 100.00,
            token: 'card-token-xyz',
            paymentMethodId: 'master',
            paymentTypeId: 'credit_card',
            email: 'test2@example.com',
            installments: 1,
            externalReference: 'CART-XYZ',
        );

        $this->assertCount(2, $capturedKeys);
        $this->assertNotEquals($capturedKeys[0], $capturedKeys[1]);
        $this->assertEquals(hash('sha256', 'CART-ABC'), $capturedKeys[0]);
        $this->assertEquals(hash('sha256', 'CART-XYZ'), $capturedKeys[1]);
    }

    public function test_refund_order_sends_empty_post_for_total_refund(): void
    {
        $capturedBodies = [];

        Http::fake([
            'api.mercadopago.com/v1/orders/*/refund' => function ($request) use (&$capturedBodies) {
                $capturedBodies[] = $request->body();

                return Http::response([
                    'id' => 'REFUND-123',
                    'status' => 'approved',
                ], 201);
            },
        ]);

        $service = new MercadoPagoService();
        $result = $service->refundOrder('ORD-TEST-123');

        $this->assertEquals('approved', $result['status']);
        $this->assertCount(1, $capturedBodies);
        $this->assertEquals('{}', $capturedBodies[0], 'Total refund sends empty JSON object');
    }

    public function test_refund_order_throws_on_api_error(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders/*/refund' => Http::response([
                'error' => 'order not found',
            ], 404),
        ]);

        $service = new MercadoPagoService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MercadoPago API error');

        $service->refundOrder('ORD-INVALID');
    }

    public function test_refund_payment_sends_amount_for_partial_refund(): void
    {
        $capturedBodies = [];

        Http::fake([
            'api.mercadopago.com/v1/payments/*/refunds' => function ($request) use (&$capturedBodies) {
                $capturedBodies[] = $request->body();

                return Http::response([
                    'id' => 'REFUND-456',
                    'status' => 'approved',
                ], 201);
            },
        ]);

        $service = new MercadoPagoService();
        $result = $service->refundPayment('PAY-789', 50.00);

        $this->assertEquals('approved', $result['status']);
        $body = json_decode($capturedBodies[0], true);
        $this->assertEquals(50.00, $body['amount']);
    }

    public function test_refund_payment_throws_on_api_error(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/*/refunds' => Http::response([
                'error' => 'payment not found',
            ], 404),
        ]);

        $service = new MercadoPagoService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MercadoPago API error');

        $service->refundPayment('PAY-INVALID', 10.00);
    }
}
