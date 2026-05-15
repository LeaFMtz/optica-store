<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Base\CartSessionInterface;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\CartAddress;
use Lunar\Models\Channel;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\TaxClass;
use Tests\TestCase;

class CheckoutShippingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function makeCart(): Cart
    {
        $channel = Channel::factory()->create(['default' => true]);
        $currency = Currency::factory()->create(['code' => 'ARS', 'decimal_places' => 2, 'default' => true]);
        TaxClass::factory()->create(['name' => 'Default', 'default' => true]);
        $country = Country::factory()->create();

        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'channel_id' => $channel->id,
        ]);

        CartAddress::factory()->create([
            'cart_id' => $cart->id,
            'country_id' => $country->id,
            'type' => 'shipping',
            'contact_email' => 'test@example.com',
        ]);

        return $cart;
    }

    public function test_selecting_retloc_option_returns_200(): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);
        $mock->shouldReceive('setShippingOption')->once()->andReturn(null);

        $response = $this->postJson('/checkout/shipping', ['identifier' => 'RETLOC']);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Shipping option selected.']);
    }

    public function test_selecting_unknown_identifier_returns_422(): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);
        $mock->shouldReceive('setShippingOption')->never();

        $response = $this->postJson('/checkout/shipping', ['identifier' => 'NONEXISTENT']);

        $response->assertStatus(422);
    }

    public function test_selecting_zipnova_option_from_session_returns_200(): void
    {
        $cart = $this->makeCart();

        session(['zipnova_quote_options' => [
            'ZN_208_standard_delivery' => [
                'identifier' => 'ZN_208_standard_delivery',
                'name' => 'OCA — Entrega a domicilio',
                'price' => 10588,
                'currency' => 'ARS',
                'estimated_days' => '4–5 días',
                'logistic_type' => 'carrier_dropoff',
                'carrier_logo' => '',
                'service_type_code' => 'standard_delivery',
                'pickup_points' => [],
            ],
        ]]);

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);
        $mock->shouldReceive('setShippingOption')->once()->andReturn(null);

        $response = $this->postJson('/checkout/shipping', ['identifier' => 'ZN_208_standard_delivery']);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Shipping option selected.']);
    }

    public function test_selecting_standard_delivery_does_not_require_point_id(): void
    {
        $cart = $this->makeCart();

        session(['zipnova_quote_options' => [
            'ZN_208_standard_delivery' => [
                'identifier' => 'ZN_208_standard_delivery',
                'name' => 'OCA — Entrega a domicilio',
                'price' => 10588,
                'currency' => 'ARS',
                'estimated_days' => '4–5 días',
                'logistic_type' => 'carrier_dropoff',
                'carrier_logo' => '',
                'service_type_code' => 'standard_delivery',
                'pickup_points' => [],
            ],
        ]]);

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);
        $mock->shouldReceive('setShippingOption')->once()->andReturn(null);

        $response = $this->postJson('/checkout/shipping', ['identifier' => 'ZN_208_standard_delivery']);

        $response->assertStatus(200);
        $this->assertNull(session('zipnova_pending_point_id'));
    }

    public function test_selecting_pickup_point_without_point_id_returns_422(): void
    {
        $cart = $this->makeCart();

        session(['zipnova_quote_options' => [
            'ZN_233_pickup_point' => [
                'identifier' => 'ZN_233_pickup_point',
                'name' => 'Correo Argentino — Entrega en punto de entrega',
                'price' => 10962,
                'currency' => 'ARS',
                'estimated_days' => '6–7 días',
                'logistic_type' => 'carrier_dropoff',
                'carrier_logo' => '',
                'service_type_code' => 'pickup_point',
                'pickup_points' => [
                    ['point_id' => 40040, 'description' => 'Correo Argentino - Recoleta', 'location' => []],
                ],
            ],
        ]]);

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);
        $mock->shouldReceive('setShippingOption')->never();

        $response = $this->postJson('/checkout/shipping', ['identifier' => 'ZN_233_pickup_point']);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Debe seleccionar un punto de retiro.']);
    }

    public function test_selecting_pickup_point_with_valid_point_id_stores_in_session(): void
    {
        $cart = $this->makeCart();

        session(['zipnova_quote_options' => [
            'ZN_233_pickup_point' => [
                'identifier' => 'ZN_233_pickup_point',
                'name' => 'Correo Argentino — Entrega en punto de entrega',
                'price' => 10962,
                'currency' => 'ARS',
                'estimated_days' => '6–7 días',
                'logistic_type' => 'carrier_dropoff',
                'carrier_logo' => '',
                'service_type_code' => 'pickup_point',
                'pickup_points' => [
                    ['point_id' => 40040, 'description' => 'Correo Argentino - Recoleta', 'location' => []],
                ],
            ],
        ]]);

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);
        $mock->shouldReceive('setShippingOption')->once()->andReturn(null);

        $response = $this->postJson('/checkout/shipping', [
            'identifier' => 'ZN_233_pickup_point',
            'point_id' => 40040,
        ]);

        $response->assertStatus(200);
        $this->assertSame(40040, session('zipnova_pending_point_id'));
    }

    public function test_selecting_zipnova_option_not_in_session_returns_422(): void
    {
        $cart = $this->makeCart();

        session(['zipnova_quote_options' => []]);

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);
        $mock->shouldReceive('setShippingOption')->never();

        $response = $this->postJson('/checkout/shipping', ['identifier' => 'ZN_999_unknown']);

        $response->assertStatus(422);
    }

    public function test_missing_identifier_returns_422(): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);

        $response = $this->postJson('/checkout/shipping', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['identifier']);
    }

    public function test_no_cart_returns_422(): void
    {
        $user = User::factory()->create();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn(null);

        $response = $this->actingAs($user)->postJson('/checkout/shipping', ['identifier' => 'RETLOC']);

        $response->assertStatus(422);
    }
}
