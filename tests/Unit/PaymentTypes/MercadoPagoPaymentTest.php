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

        $result = $payment->authorize();

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not configured', $result->message);
    }

    public function test_capture_returns_success(): void
    {
        $order = Order::factory()->create();

        $payment = new MercadoPagoPayment;
        $payment->order($order);

        $transaction = new Transaction([
            'success' => true,
            'type' => 'intent',
            'driver' => 'mercadopago',
            'amount' => 10000,
            'reference' => '12345',
            'status' => 'pending',
            'card_type' => 'visa',
            'last_four' => '4242',
        ]);

        $result = $payment->capture($transaction);

        $this->assertTrue($result->success);
    }

    public function test_refund_returns_error_when_not_configured(): void
    {
        config(['services.mercadopago.access_token' => null]);

        $order = Order::factory()->create();

        $transaction = new Transaction([
            'success' => true,
            'type' => 'capture',
            'driver' => 'mercadopago',
            'amount' => 10000,
            'reference' => '12345',
            'status' => 'succeeded',
            'card_type' => 'visa',
            'last_four' => '4242',
        ]);

        $payment = new MercadoPagoPayment;
        $payment->order($order);

        $result = $payment->refund($transaction, 10000);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not configured', $result->message);
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
