<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_returns_400_when_not_configured(): void
    {
        Config::set('services.mercadopago.access_token', null);

        $response = $this->postJson('/webhooks/mercadopago', [
            'topic' => 'payment',
            'id' => '12345',
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_ignores_non_payment_topics(): void
    {
        Config::set('services.mercadopago.access_token', 'test_token');

        $response = $this->postJson('/webhooks/mercadopago', [
            'topic' => 'other',
            'id' => '12345',
        ]);

        $response->assertJson(['status' => 'ignored']);
    }

    public function test_webhook_returns_404_when_id_missing(): void
    {
        Config::set('services.mercadopago.access_token', 'test_token');

        $response = $this->postJson('/webhooks/mercadopago', [
            'topic' => 'payment',
        ]);

        $response->assertJson(['status' => 'ignored']);
    }
}
