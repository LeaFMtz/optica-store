<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\PaymentTypes\MercadoPagoPayment;
use App\Services\MercadoPagoService;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Models\Contracts\Transaction;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class MercadoPagoRefundTest extends TestCase
{
    public function test_refund_returns_failure_when_mp_order_id_missing_from_meta(): void
    {
        $mpService = Mockery::mock(MercadoPagoService::class);
        $payment = new MercadoPagoPayment($mpService);

        $transaction = Mockery::mock(Transaction::class);
        $transaction->meta = [];
        $transaction->order_id = null;

        $result = $payment->refund($transaction, 0);

        $this->assertInstanceOf(PaymentRefund::class, $result);
        $this->assertFalse($result->success);
        $this->assertTrue(str_contains($result->message, 'mp_order_id'));
    }

    public function test_total_refund_returns_success_when_mp_returns_refunded(): void
    {
        $mpService = Mockery::mock(MercadoPagoService::class);
        $mpService->shouldReceive('refundOrder')
            ->with('ORD-TEST-123')
            ->once()
            ->andReturn(['id' => 'REFUND-001', 'status' => 'refunded']);

        $payment = new MercadoPagoPayment($mpService);

        $transaction = Mockery::mock(Transaction::class);
        $transaction->meta = ['mp_order_id' => 'ORD-TEST-123'];
        $transaction->order_id = null;

        $result = $payment->refund($transaction, 0);

        $this->assertInstanceOf(PaymentRefund::class, $result);
        $this->assertTrue($result->success);
    }

    public function test_refund_returns_failure_when_mp_status_is_not_refunded(): void
    {
        $mpService = Mockery::mock(MercadoPagoService::class);
        $mpService->shouldReceive('refundOrder')
            ->with('ORD-TEST-456')
            ->once()
            ->andReturn(['id' => 'REFUND-002', 'status' => 'rejected']);

        $payment = new MercadoPagoPayment($mpService);

        $transaction = Mockery::mock(Transaction::class);
        $transaction->meta = ['mp_order_id' => 'ORD-TEST-456'];
        $transaction->order_id = null;

        $result = $payment->refund($transaction, 0);

        $this->assertFalse($result->success);
    }

    public function test_refund_returns_failure_on_runtime_exception(): void
    {
        $mpService = Mockery::mock(MercadoPagoService::class);
        $mpService->shouldReceive('refundOrder')
            ->with('ORD-TEST-999')
            ->once()
            ->andThrow(new \RuntimeException('MercadoPago API error: 500'));

        $payment = new MercadoPagoPayment($mpService);

        $transaction = Mockery::mock(Transaction::class);
        $transaction->meta = ['mp_order_id' => 'ORD-TEST-999'];
        $transaction->order_id = null;

        $result = $payment->refund($transaction, 0);

        $this->assertFalse($result->success);
        $this->assertTrue(str_contains($result->message, 'MercadoPago API error'));
    }

    public function test_refund_skips_transaction_creation_when_order_id_null(): void
    {
        $mpService = Mockery::mock(MercadoPagoService::class);
        $mpService->shouldReceive('refundOrder')
            ->with('ORD-TEST-777')
            ->once()
            ->andReturn(['id' => 'REF-777', 'status' => 'refunded']);

        $payment = new MercadoPagoPayment($mpService);

        $transaction = Mockery::mock(Transaction::class);
        $transaction->meta = ['mp_order_id' => 'ORD-TEST-777'];
        $transaction->order_id = null;
        $transaction->amount = 1000;
        $transaction->card_type = 'credit_card';

        $result = $payment->refund($transaction, 0);

        // Still returns success even without order
        $this->assertTrue($result->success);
    }
}
