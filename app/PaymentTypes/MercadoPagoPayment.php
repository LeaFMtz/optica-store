<?php

declare(strict_types=1);

namespace App\PaymentTypes;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\PaymentTypes\AbstractPayment;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\APIException;
use MercadoPago\Resources\Payment;

class MercadoPagoPayment extends AbstractPayment
{
    protected ?PaymentClient $client = null;

    public function __construct()
    {
        $this->client = new PaymentClient;
    }

    public function authorize(): ?PaymentAuthorize
    {
        $accessToken = config('services.mercadopago.access_token');

        if (!$accessToken) {
            $failure = new PaymentAuthorize(
                success: false,
                message: 'Mercado Pago is not configured',
                paymentType: 'mercadopago',
            );
            PaymentAttemptEvent::dispatch($failure);

            return $failure;
        }

        $paymentToken = $this->data['payment_token'] ?? null;
        $payerEmail = $this->data['payer_email'] ?? $this->cart?->customer?->email;
        $identification = $this->data['identification'] ?? null;

        if (!$paymentToken) {
            $failure = new PaymentAuthorize(
                success: false,
                message: 'Payment token is required',
                paymentType: 'mercadopago',
            );
            PaymentAttemptEvent::dispatch($failure);

            return $failure;
        }

        if (!$this->order) {
            $this->order = $this->cart?->order ?: $this->cart?->createOrder();
        }

        if (!$this->order) {
            $failure = new PaymentAuthorize(
                success: false,
                message: 'Unable to create order',
                paymentType: 'mercadopago',
            );
            PaymentAttemptEvent::dispatch($failure);

            return $failure;
        }

        try {
            $payment = $this->createPayment(
                token: $paymentToken,
                transactionAmount: $this->order->total->value / 100,
                description: "Order #{$this->order->reference_number}",
                paymentMethodId: $this->data['payment_method_id'] ?? 'credit_card',
                email: $payerEmail,
                identification: $identification,
                orderId: (string) $this->order->id,
            );
        } catch (\Exception $e) {
            $failure = new PaymentAuthorize(
                success: false,
                message: $e->getMessage(),
                orderId: $this->order->id,
                paymentType: 'mercadopago',
            );
            PaymentAttemptEvent::dispatch($failure);

            return $failure;
        }

        $transactionType = $payment->status === 'approved' ? 'capture' : 'intent';
        $transactionSuccess = in_array($payment->status, ['approved', 'pending', 'authorized']);

        $this->order->transactions()->create([
            'success' => $transactionSuccess,
            'type' => $transactionType,
            'driver' => 'mercadopago',
            'amount' => (int) ($payment->transaction_amount * 100),
            'reference' => (string) $payment->id,
            'status' => $payment->status,
            'meta' => [
                'payment_id' => $payment->id,
                'status_detail' => $payment->status_detail,
                'payment_type' => $payment->payment_type_id,
                'card_brand' => $payment->card?->first_card_bin,
                'last_four' => $payment->card?->last_four_digits,
                'installments' => $payment->installments,
            ],
        ]);

        $response = new PaymentAuthorize(
            success: $transactionSuccess,
            message: $this->getStatusMessage($payment),
            orderId: $this->order->id,
            paymentType: 'mercadopago',
        );
        PaymentAttemptEvent::dispatch($response);

        return $response;
    }

    public function capture(TransactionContract $transaction, $amount = 0): PaymentCapture
    {
        $paymentId = $transaction->reference;

        try {
            $payment = $this->client->get($paymentId, $this->getRequestOptions());

            if ($payment->status !== 'authorized') {
                return new PaymentCapture(
                    success: false,
                    message: 'Payment cannot be captured',
                );
            }

            $captureAmount = $amount > 0 ? $amount : $transaction->amount;
            $updatedPayment = $this->client->capture($paymentId, $captureAmount / 100, $this->getRequestOptions());

            $this->order->transactions()->create([
                'success' => $updatedPayment->status === 'approved',
                'type' => 'capture',
                'driver' => 'mercadopago',
                'amount' => (int) ($updatedPayment->transaction_amount * 100),
                'reference' => (string) $updatedPayment->id,
                'status' => $updatedPayment->status,
                'parent_transaction_id' => $transaction->id,
            ]);

            return new PaymentCapture(
                success: $updatedPayment->status === 'approved',
                message: $this->getStatusMessage($updatedPayment),
            );
        } catch (APIException $e) {
            return new PaymentCapture(
                success: false,
                message: $e->getMessage(),
            );
        }
    }

    public function refund(TransactionContract $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        $paymentId = $transaction->reference;

        try {
            $refundAmount = $amount > 0 ? $amount : $transaction->amount;

            $payload = [
                'amount' => $refundAmount / 100,
            ];

            $response = $this->client->refund($paymentId, $payload, $this->getRequestOptions());

            $this->order->transactions()->create([
                'success' => $response->status === 'approved',
                'type' => 'refund',
                'driver' => 'mercadopago',
                'amount' => $refundAmount,
                'reference' => (string) $paymentId,
                'status' => $response->status,
                'parent_transaction_id' => $transaction->id,
                'notes' => $notes,
            ]);

            return new PaymentRefund(
                success: $response->status === 'approved',
                message: $response->status === 'approved' ? 'Refund processed' : 'Refund failed',
            );
        } catch (APIException $e) {
            return new PaymentRefund(
                success: false,
                message: $e->getMessage(),
            );
        }
    }

    protected function getRequestOptions(): RequestOptions
    {
        $accessToken = config('services.mercadopago.access_token');

        return new RequestOptions([
            'access_token' => $accessToken,
        ]);
    }

    protected function createPayment(
        string $token,
        float $transactionAmount,
        string $description,
        string $paymentMethodId,
        ?string $email,
        ?array $identification,
        string $orderId,
    ): Payment {
        $payer = [
            'email' => $email,
        ];

        if ($identification) {
            $payer['identification'] = [
                'type' => $identification['type'] ?? 'DNI',
                'number' => $identification['number'] ?? '',
            ];
        }

        $payload = [
            'token' => $token,
            'transaction_amount' => $transactionAmount,
            'description' => $description,
            'payment_method_id' => $paymentMethodId,
            'payer' => $payer,
            'external_reference' => $orderId,
            'capture' => true,
            'installments' => (int) ($this->data['installments'] ?? 1),
        ];

        return $this->client->create($payload, $this->getRequestOptions());
    }

    protected function getStatusMessage(Payment $payment): string
    {
        return match ($payment->status) {
            'approved' => 'Payment approved',
            'pending' => 'Payment pending',
            'authorized' => 'Payment authorized',
            'in_process' => 'Payment in process',
            'in_mediation' => 'Payment in mediation',
            'rejected' => $payment->status_detail ?: 'Payment rejected',
            'cancelled' => 'Payment cancelled',
            'refunded' => 'Payment refunded',
            default => 'Unknown status',
        };
    }
}
