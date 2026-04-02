<?php

declare(strict_types=1);

namespace App\PaymentTypes;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\PaymentTypes\AbstractPayment;
use MercadoPago\Item;
use MercadoPago\Payment;
use MercadoPago\Preference;
use MercadoPago\Refund;
use MercadoPago\SDK;

class MercadoPagoPayment extends AbstractPayment
{
    public function authorize(): ?PaymentAuthorize
    {
        if (!$this->order) {
            if (!$this->order = $this->cart->order) {
                $this->order = $this->cart->createOrder();
            }
        }

        $accessToken = config('services.mercadopago.access_token');

        if (!$accessToken) {
            return new PaymentAuthorize(
                success: false,
                message: 'Mercado Pago is not configured',
                orderId: $this->order->id,
                paymentType: 'mercadopago',
            );
        }

        SDK::setAccessToken($accessToken);

        $paymentPreference = [
            'items' => $this->order->lines->map(fn ($line) => [
                'title' => $line->purchasable->product->name,
                'quantity' => $line->quantity,
                'currency_id' => $this->order->currency_code,
                'unit_price' => (float) $line->unitPrice->value,
            ])->toArray(),
            'external_reference' => (string) $this->order->id,
            'notification_url' => config('app.url').'/webhooks/mercadopago',
            'back_urls' => [
                'success' => route('checkout-success.view'),
                'pending' => route('checkout-success.view'),
                'failure' => route('checkout.view'),
            ],
        ];

        $preference = new Preference;
        $preference->items = array_map(fn ($item) => new Item($item), $preference::array_items($paymentPreference['items']));
        $preference->external_reference = $paymentPreference['external_reference'];
        $preference->notification_url = $paymentPreference['notification_url'];
        $preference->back_urls = $paymentPreference['back_urls'];
        $preference->save();

        $transaction = $this->order->transactions()->create([
            'success' => true,
            'type' => 'intent',
            'driver' => 'mercadopago',
            'amount' => $this->order->total->value,
            'reference' => $preference->id,
            'status' => 'pending',
            'meta' => [
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point ?? $preference->sandbox_init_point,
            ],
        ]);

        return new PaymentAuthorize(
            success: true,
            message: 'Payment preference created',
            orderId: $this->order->id,
            paymentType: 'mercadopago',
        );
    }

    public function capture(TransactionContract $transaction, $amount = 0): PaymentCapture
    {
        return new PaymentCapture(success: true);
    }

    public function refund(TransactionContract $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        $accessToken = config('services.mercadopago.access_token');

        if (!$accessToken) {
            return new PaymentRefund(false, 'Mercado Pago is not configured');
        }

        SDK::setAccessToken($accessToken);

        $paymentId = $transaction->reference;

        try {
            $payment = Payment::find_by_id($paymentId);

            if ($payment && $payment->status === 'approved') {
                $refundAmount = $amount > 0 ? $amount : $transaction->amount;

                $refund = new Refund;
                $refund->payment_id = $paymentId;
                $refund->amount = $refundAmount / 100;
                $refund->save();

                $this->order->transactions()->create([
                    'success' => $refund->status === 'approved',
                    'type' => 'refund',
                    'driver' => 'mercadopago',
                    'amount' => $refundAmount,
                    'reference' => $paymentId,
                    'status' => $refund->status ?? 'failed',
                ]);

                return new PaymentRefund(
                    success: $refund->status === 'approved',
                    message: $refund->message ?? 'Refund processed',
                );
            }

            return new PaymentRefund(false, 'Payment not found or not approved');
        } catch (\Exception $e) {
            return new PaymentRefund(false, $e->getMessage());
        }
    }
}
