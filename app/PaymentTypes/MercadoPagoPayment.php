<?php

declare(strict_types=1);

namespace App\PaymentTypes;

use Illuminate\Support\Facades\Log;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentChecks;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Models\Contracts\Cart as CartContract;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;
use MercadoPago\Client\Payment\PaymentRefundClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoPayment extends AbstractPayment
{
    private string $accessToken;

    private bool $sandbox;

    /**
     * Create a new MercadoPago payment driver instance.
     */
    public function __construct()
    {
        $config = config('services.mercadopago', []);

        $this->accessToken = (string) ($config['access_token'] ?? '');
        $this->sandbox = (bool) ($config['sandbox'] ?? true);

        if ($this->accessToken !== '') {
            MercadoPagoConfig::setAccessToken($this->accessToken);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function cart(CartContract $cart): self
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Authorize the payment by creating a MercadoPago Checkout Pro preference.
     */
    public function authorize(): ?PaymentAuthorize
    {
        $cart = $this->cart;

        if (! $cart) {
            return new PaymentAuthorize(
                success: false,
                message: 'No cart set on payment driver',
            );
        }

        if ($this->accessToken === '') {
            Log::warning('MercadoPago credentials not configured');

            return new PaymentAuthorize(
                success: false,
                message: 'MercadoPago payment method not configured',
            );
        }

        try {
            if (! $this->order) {
                $this->order = $cart->createOrder();
            }

            $order = $this->order;

            if (! $order) {
                return new PaymentAuthorize(
                    success: false,
                    message: 'Unable to create order from cart',
                );
            }

            $items = [];

            foreach ($cart->lines as $line) {
                if ($line->purchasable_type !== 'product_variant') {
                    continue;
                }

                $items[] = [
                    'title' => $line->purchasable->getDescription(),
                    'quantity' => (int) $line->quantity,
                    'unit_price' => (float) ($line->unitPrice->value / 100),
                    'currency_id' => 'ARS',
                ];
            }

            $preferenceData = [
                'items' => $items,
                'payer' => [
                    'email' => $cart->billingAddress?->contact_email ?? 'test@test.com',
                ],
                'external_reference' => $order->reference,
                'back_urls' => [
                    'success' => url('/checkout/success?source=mp'),
                    'failure' => url('/checkout?error=mp_failed'),
                    'pending' => url('/checkout?error=mp_pending'),
                ],
                'auto_return' => 'approved',
            ];

            $client = new PreferenceClient();
            $preference = $client->create($preferenceData);

            $initPoint = $this->sandbox && $preference->sandbox_init_point
                ? $preference->sandbox_init_point
                : $preference->init_point;

            Transaction::create([
                'order_id' => $order->id,
                'amount' => $order->total->value,
                'success' => false,
                'type' => 'intent',
                'status' => 'pending',
                'reference' => $preference->id,
                'driver' => 'mercadopago',
                'meta' => [
                    'preference_id' => $preference->id,
                    'init_point' => $initPoint,
                ],
            ]);

            return new PaymentAuthorize(
                success: true,
                orderId: $order->id,
                message: $initPoint ?? '',
            );
        } catch (MPApiException $e) {
            Log::error('MercadoPago API error: ' . $e->getMessage(), [
                'status_code' => $e->getStatusCode(),
                'api_response' => $e->getApiResponse(),
                'order_id' => $this->order?->id,
            ]);

            return new PaymentAuthorize(
                success: false,
                message: 'Error al procesar el pago: ' . $e->getMessage(),
            );
        } catch (\Exception $e) {
            Log::error('MercadoPago payment authorization failed: ' . $e->getMessage(), [
                'exception' => $e,
                'order_id' => $this->order?->id ?? null,
                'cart_id' => $this->cart?->id ?? null,
            ]);

            return new PaymentAuthorize(
                success: false,
                message: 'Error processing payment: ' . $e->getMessage(),
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function capture(TransactionContract $transaction, $amount = 0): PaymentCapture
    {
        return new PaymentCapture(
            success: true,
            message: 'Payment captured automatically by MercadoPago Checkout Pro',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function refund(TransactionContract $transaction, int $amount, $notes = null): PaymentRefund
    {
        try {
            $paymentId = $transaction->meta['mp_payment_id'] ?? null;

            if (! $paymentId) {
                return new PaymentRefund(
                    success: false,
                    message: 'No MercadoPago payment ID found for refund',
                );
            }

            $client = new PaymentRefundClient();
            $refund = $client->refund((int) $paymentId, (float) ($amount / 100));

            return new PaymentRefund(
                success: $refund->id !== null,
                message: $refund->id ? 'Refund processed successfully' : 'Refund failed',
            );
        } catch (\Exception $e) {
            Log::error('MercadoPago refund failed: ' . $e->getMessage(), [
                'exception' => $e,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
            ]);

            return new PaymentRefund(
                success: false,
                message: 'Error processing refund: ' . $e->getMessage(),
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentChecks(TransactionContract $transaction): PaymentChecks
    {
        return new PaymentChecks();
    }
}
