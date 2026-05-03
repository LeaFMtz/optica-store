<?php

declare(strict_types=1);

namespace App\Services;

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoService
{
    /**
     * Configure the MP SDK access token lazily (only when making API calls).
     * This avoids failures during container bootstrapping when the env var is null (e.g. tests).
     */
    private function configure(): void
    {
        MercadoPagoConfig::setAccessToken(
            (string) config('services.mercadopago.access_token', ''),
        );
    }

    /**
     * Charge a payment via the MercadoPago API.
     *
     * @param  float  $amount  Amount in ARS (NOT centavos — already converted)
     * @param  string  $token  Card token from the Payment Brick
     * @param  string  $paymentMethodId  e.g. "visa"
     * @param  string  $issuerId  Bank issuer id
     * @param  string  $email  Payer email
     * @param  int  $installments  Number of installments
     * @return array<string, mixed>  Raw MP API response array
     *
     * @throws \RuntimeException on network/5xx errors
     */
    public function charge(
        float $amount,
        string $token,
        string $paymentMethodId,
        string $issuerId,
        string $email,
        int $installments = 1,
    ): array {
        $this->configure();

        $client = new PaymentClient;

        $request = [
            'transaction_amount' => $amount,
            'token' => $token,
            'description' => 'Compra en Óptica Store',
            'installments' => $installments,
            'payment_method_id' => $paymentMethodId,
            'issuer_id' => (int) $issuerId,
            'payer' => [
                'email' => $email,
            ],
        ];

        try {
            $payment = $client->create($request);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'MercadoPago API error: '.$e->getMessage(),
                0,
                $e,
            );
        }

        return (array) $payment;
    }

    /**
     * Fetch a payment by ID from the MercadoPago API.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException on network/5xx errors
     */
    public function getPayment(int $paymentId): array
    {
        $this->configure();

        $client = new PaymentClient;

        try {
            $payment = $client->get($paymentId);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'MercadoPago API error fetching payment: '.$e->getMessage(),
                0,
                $e,
            );
        }

        return (array) $payment;
    }
}
