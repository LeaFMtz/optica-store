<?php

declare(strict_types=1);

namespace App\PaymentTypes;

use App\Services\MercadoPagoService;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Models\Contracts\Transaction;
use Lunar\PaymentTypes\AbstractPayment;

class MercadoPagoPayment extends AbstractPayment
{
    /**
     * The MercadoPago order ID returned after a successful order creation.
     * Set by authorize() so the controller can persist it on the order.
     */
    public ?string $lastOrderId = null;

    public function __construct(private readonly MercadoPagoService $mpService) {}

    /**
     * Authorize the payment by creating a MercadoPago order.
     *
     * Expects $this->data to contain: token, payment_method_id, issuer_id, installments.
     * Expects $this->cart to be set with a valid total.
     */
    public function authorize(): PaymentAuthorize
    {
        if (!$this->cart) {
            return new PaymentAuthorize(
                success: false,
                message: 'No cart attached to payment driver.',
            );
        }

        $token = $this->data['token'] ?? null;
        $paymentMethodId = $this->data['payment_method_id'] ?? null;
        $paymentTypeId = $this->data['payment_type_id'] ?? 'credit_card';
        $installments = (int) ($this->data['installments'] ?? 1);
        $email = $this->data['payer_email'] ?? $this->cart->user?->email ?? '';

        if (!$token || !$paymentMethodId) {
            return new PaymentAuthorize(
                success: false,
                message: 'Missing payment token or method.',
            );
        }

        // Lunar stores amounts as integer centavos — divide by 100 for MP
        $amount = $this->cart->total->value / 100;

        try {
            $response = $this->mpService->createOrder(
                amount: $amount,
                token: $token,
                paymentMethodId: $paymentMethodId,
                paymentTypeId: $paymentTypeId,
                email: $email,
                installments: $installments,
                externalReference: $this->cart->reference ?? null,
            );
        } catch (\RuntimeException $e) {
            return new PaymentAuthorize(
                success: false,
                message: $e->getMessage(),
            );
        }

        $status = $response['status'] ?? 'rejected';
        $statusDetail = $response['status_detail'] ?? 'unknown';
        $orderId = $response['id'] ?? null;

        if (!$orderId) {
            return new PaymentAuthorize(
                success: false,
                message: 'No order ID returned from MercadoPago.',
            );
        }

        $this->lastOrderId = (string) $orderId;

        // processed + accredited → payment fully approved and captured
        if ($status === 'processed' && $statusDetail === 'accredited') {
            return new PaymentAuthorize(
                success: true,
                message: 'accredited',
                paymentType: 'mercadopago',
            );
        }

        // processing → payment is async, webhook will update later
        if ($status === 'processing') {
            return new PaymentAuthorize(
                success: true,
                message: 'pending',
                paymentType: 'mercadopago',
            );
        }

        // Any other status (failed, canceled, expired, rejected) → not approved
        return new PaymentAuthorize(
            success: false,
            message: $statusDetail,
            paymentType: 'mercadopago',
        );
    }

    /**
     * Capture is not supported — MP charges immediately on authorize.
     */
    public function capture(Transaction $transaction, $amount = null): PaymentCapture
    {
        return new PaymentCapture(
            success: false,
            message: 'MercadoPago does not support separate capture.',
        );
    }

    /**
     * Refunds are not supported in Phase 1.
     */
    public function refund(Transaction $transaction, int $amount, $notes = null): PaymentRefund
    {
        return new PaymentRefund(
            success: false,
            message: 'Refunds via MercadoPago are not supported in Phase 1.',
        );
    }
}
