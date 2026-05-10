<?php

declare(strict_types=1);

namespace App\PaymentTypes;

use App\Services\MercadoPagoService;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\PaymentTypes\AbstractPayment;

class MercadoPagoPayment extends AbstractPayment
{
    /**
     * The MercadoPago payment ID returned after a successful charge.
     * Set by authorize() so the controller can persist it on the order.
     */
    public ?int $lastPaymentId = null;

    public function __construct(private readonly MercadoPagoService $mpService) {}

    /**
     * Authorize the payment by charging via MercadoPago.
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
        $issuerId = $this->data['issuer_id'] ?? '';
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
            $response = $this->mpService->charge(
                amount: $amount,
                token: $token,
                paymentMethodId: $paymentMethodId,
                issuerId: (string) $issuerId,
                email: $email,
                installments: $installments,
            );
        } catch (\RuntimeException $e) {
            return new PaymentAuthorize(
                success: false,
                message: $e->getMessage(),
            );
        }

        $status = $response['status'] ?? 'rejected';
        $paymentId = $response['id'] ?? null;

        if ($status === 'approved' && $paymentId) {
            $this->lastPaymentId = (int) $paymentId;

            return new PaymentAuthorize(
                success: true,
                message: 'approved',
                paymentType: 'mercadopago',
            );
        }

        $statusDetail = $response['status_detail'] ?? 'payment_rejected';

        return new PaymentAuthorize(
            success: false,
            message: $statusDetail,
            paymentType: 'mercadopago',
        );
    }

    /**
     * Capture is not supported — MP charges immediately on authorize.
     */
    public function capture(\Lunar\Models\Contracts\Transaction $transaction, $amount = null): PaymentCapture
    {
        return new PaymentCapture(
            success: false,
            message: 'MercadoPago does not support separate capture.',
        );
    }

    /**
     * Refunds are not supported in Phase 1.
     */
    public function refund(\Lunar\Models\Contracts\Transaction $transaction, int $amount, $notes = null): PaymentRefund
    {
        return new PaymentRefund(
            success: false,
            message: 'Refunds via MercadoPago are not supported in Phase 1.',
        );
    }
}
