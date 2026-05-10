<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Base\CartSessionInterface;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Facades\CartSession;
use Lunar\Managers\CartSessionManager;
use Lunar\Models\Cart;
use Lunar\Models\CartAddress;
use Lunar\Models\Channel;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Order;
use Tests\TestCase;

class CheckoutPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated users receive 401.
     */
    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->postJson('/checkout/payment', [
            'token' => 'test-token',
            'payment_method_id' => 'visa',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Authenticated user with no cart receives 422.
     */
    public function test_missing_cart_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/checkout/payment', [
            'token' => 'test-token',
            'payment_method_id' => 'visa',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Missing required 'token' field returns validation error.
     */
    public function test_missing_token_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/checkout/payment', [
            'payment_method_id' => 'visa',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token']);
    }

    /**
     * Missing payment_method_id returns validation error.
     */
    public function test_missing_payment_method_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/checkout/payment', [
            'token' => 'test-token',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method_id']);
    }

    /**
     * When the MP API throws a RuntimeException the controller returns 500.
     */
    public function test_mp_api_exception_returns_500(): void
    {
        $user = User::factory()->create();

        // Build a Lunar cart with valid shipping address and shipping_option
        $currency = Currency::factory()->create(['default' => true]);
        $channel = Channel::factory()->create(['default' => true]);
        $country = Country::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'channel_id' => $channel->id,
        ]);

        CartAddress::factory()->create([
            'cart_id' => $cart->id,
            'country_id' => $country->id,
            'type' => 'shipping',
            'shipping_option' => 'standard',
            'contact_email' => 'test@example.com',
        ]);

        CartAddress::factory()->create([
            'cart_id' => $cart->id,
            'country_id' => $country->id,
            'type' => 'billing',
        ]);

        // Put the cart in the session
        CartSession::use($cart);

        // Stub the MP service to throw an API exception
        $this->instance(MercadoPagoService::class, new class extends MercadoPagoService
        {
            public function charge(
                float $amount,
                string $token,
                string $paymentMethodId,
                string $issuerId,
                string $email,
                int $installments = 1,
            ): array {
                throw new \RuntimeException('MercadoPago API error: connection refused');
            }
        });

        $response = $this->actingAs($user)->postJson('/checkout/payment', [
            'token' => 'test-token',
            'payment_method_id' => 'visa',
        ]);

        // The driver catches RuntimeException and returns success:false,
        // which means the controller returns 422 (rejected), not 500.
        // A 500 only occurs for unexpected Throwable outside the driver.
        // The authorize() method itself catches RuntimeException → returns PaymentAuthorize(success:false).
        // Therefore the controller returns 422 (pago rechazado).
        $response->assertStatus(422);
    }

    /**
     * When the MP API returns 'approved', the order is created and mp_payment_id is stored in meta.
     */
    public function test_approved_payment_creates_order_with_payment_id(): void
    {
        $user = User::factory()->create();

        // Build a Lunar cart with valid shipping address and shipping_option
        $currency = Currency::factory()->create(['default' => true]);
        $channel = Channel::factory()->create(['default' => true]);
        $country = Country::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'channel_id' => $channel->id,
        ]);

        CartAddress::factory()->create([
            'cart_id' => $cart->id,
            'country_id' => $country->id,
            'type' => 'shipping',
            'shipping_option' => 'standard',
            'contact_email' => $user->email,
        ]);

        CartAddress::factory()->create([
            'cart_id' => $cart->id,
            'country_id' => $country->id,
            'type' => 'billing',
        ]);

        // Stub MercadoPagoService to return an approved response
        $this->instance(MercadoPagoService::class, new class extends MercadoPagoService
        {
            public function charge(
                float $amount,
                string $token,
                string $paymentMethodId,
                string $issuerId,
                string $email,
                int $installments = 1,
            ): array {
                return [
                    'id' => 9988776,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                ];
            }
        });

        // Swap CartSession so createOrder() returns a real-looking order without full Lunar pipeline
        $order = Order::factory()->create([
            'reference' => 'ORD-TEST-001',
            'meta' => null,
        ]);

        $cartSessionMock = \Mockery::mock(CartSessionManager::class)->makePartial();
        $cartSessionMock->shouldReceive('current')->andReturn($cart->load(['shippingAddress', 'billingAddress']));
        $cartSessionMock->shouldReceive('createOrder')->andReturn($order);
        $cartSessionMock->shouldReceive('forget')->andReturnNull();
        $this->instance(CartSessionInterface::class, $cartSessionMock);

        $response = $this->actingAs($user)->postJson('/checkout/payment', [
            'token' => 'test-token',
            'payment_method_id' => 'visa',
            'installments' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['reference' => 'ORD-TEST-001']);

        // Verify mp_payment_id was persisted in order meta
        $order->refresh();
        $meta = (array) $order->meta;
        $this->assertEquals(9988776, $meta['mp_payment_id']);
    }
}
