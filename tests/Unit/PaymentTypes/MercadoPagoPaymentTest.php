<?php

declare(strict_types=1);

namespace Tests\Unit\PaymentTypes;

use App\PaymentTypes\MercadoPagoPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Currency;
use Lunar\Models\Order;
use Lunar\Models\Transaction;
use Tests\TestCase;

class MercadoPagoPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_error_when_not_configured(): void
    {
        config(['services.mercadopago.access_token' => null]);

        $order = Order::factory()->create();

        $payment = new MercadoPagoPayment;
        $payment->order($order);
        $payment->withData([
            'payment_token' => 'test_token',
            'payment_method_id' => 'credit_card',
        ]);

        $result = $payment->authorize();

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not configured', $result->message);
    }

    public function test_authorize_returns_error_when_missing_token(): void
    {
        config(['services.mercadopago.access_token' => 'test_token']);

        $order = Order::factory()->create();

        $payment = new MercadoPagoPayment;
        $payment->order($order);
        $payment->withData([]);

        $result = $payment->authorize();

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Payment token is required', $result->message);
    }

    public function test_capture_creates_capture_transaction(): void
    {
        config(['services.mercadopago.access_token' => 'test_token']);

        $order = Order::factory()->create();

        $transaction = new Transaction([
            'success' => true,
            'type' => 'intent',
            'driver' => 'mercadopago',
            'amount' => 10000,
            'reference' => '12345',
            'status' => 'authorized',
            'card_type' => 'visa',
            'last_four' => '4242',
        ]);

        $payment = new MercadoPagoPayment;
        $payment->order($order);

        $result = $payment->capture($transaction);

        $this->assertTrue($result->success);
    }

    public function test_refund_creates_refund_transaction(): void
    {
        config(['services.mercadopago.access_token' => 'test_token']);

        $order = Order::factory()->create();

        $transaction = new Transaction([
            'success' => true,
            'type' => 'capture',
            'driver' => 'mercadopago',
            'amount' => 10000,
            'reference' => '12345',
            'status' => 'approved',
            'card_type' => 'visa',
            'last_four' => '4242',
        ]);

        $payment = new MercadoPagoPayment;
        $payment->order($order);

        $result = $payment->refund($transaction, 10000);

        $this->assertTrue($result->success);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Currency::factory()->create([
            'code' => 'ARS',
            'decimal_places' => 2,
        ]);
    }
}
