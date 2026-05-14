<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingQuoteControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.zipnova.mock' => true]);
        // The shipping quote endpoint uses XSRF-TOKEN cookie (same pattern as checkout).
        // In tests we bypass CSRF middleware directly.
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_valid_four_digit_cp_returns_options(): void
    {
        $response = $this->postJson('/api/shipping/quote', [
            'postcode' => '1425',
            'weight_grams' => 500,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'options' => [
                '*' => ['identifier', 'name', 'price', 'currency', 'estimated_days'],
            ],
        ]);
        $this->assertNotEmpty($response->json('options'));
    }

    public function test_invalid_cp_non_four_digit_returns_422(): void
    {
        $response = $this->postJson('/api/shipping/quote', [
            'postcode' => '12345',
            'weight_grams' => 500,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['postcode']);
    }

    public function test_cp_with_alpha_chars_returns_422(): void
    {
        $response = $this->postJson('/api/shipping/quote', [
            'postcode' => 'C142',
            'weight_grams' => 500,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['postcode']);
    }

    public function test_empty_cp_returns_422(): void
    {
        $response = $this->postJson('/api/shipping/quote', [
            'postcode' => '',
            'weight_grams' => 500,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['postcode']);
    }

    public function test_zipnova_api_runtime_exception_returns_200_with_empty_options(): void
    {
        config(['services.zipnova.mock' => false]);
        config(['services.zipnova.token' => 'test_token']);
        config(['services.zipnova.secret' => 'test_secret']);
        config(['services.zipnova.base_url' => 'https://api.zipnova.com.ar']);

        Http::fake([
            'api.zipnova.com.ar/*' => Http::response('Server error', 500),
        ]);

        $response = $this->postJson('/api/shipping/quote', [
            'postcode' => '1425',
            'weight_grams' => 500,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['options' => []]);
    }

    public function test_same_cp_and_weight_served_from_cache_on_second_request(): void
    {
        config(['services.zipnova.mock' => false]);
        config(['services.zipnova.token' => 'test_token']);
        config(['services.zipnova.secret' => 'test_secret']);
        config(['services.zipnova.base_url' => 'https://api.zipnova.com.ar']);

        Http::fake([
            'api.zipnova.com.ar/*' => Http::response([
                'status' => 'success',
                'all_results' => [
                    [
                        'service_type' => 'OCA_STANDARD',
                        'carrier_name' => 'OCA',
                        'service_name' => 'OCA Estándar',
                        'price' => 180000,
                        'currency' => 'ARS',
                        'estimated_days' => 5,
                    ],
                ],
            ], 200),
        ]);

        // First request — hits API
        $this->postJson('/api/shipping/quote', ['postcode' => '9999', 'weight_grams' => 300]);

        // Second request — should be served from cache
        $this->postJson('/api/shipping/quote', ['postcode' => '9999', 'weight_grams' => 300]);

        // Assert HTTP was called only once (cache hit on second request)
        Http::assertSentCount(1);
    }
}
