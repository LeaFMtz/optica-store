<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Lunar\Base\CartSessionInterface;
use Lunar\Models\Cart;
use Lunar\Models\CartAddress;
use Lunar\Models\CartLine;
use Lunar\Models\Channel;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutAddressControllerTest extends TestCase
{
    use RefreshDatabase;

    // ─── POST receiver saves DNI to CartAddress.meta ──────────────────────────────

    #[DataProvider('receiverDeliveryTypeProvider')]
    public function test_receiver_saves_dni_to_meta(string $deliveryType, bool $expectsAddress): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);

        $payload = [
            'save_type' => 'receiver',
            'delivery_type' => $deliveryType,
            'first_name' => 'Ana',
            'last_name' => 'García',
            'dni' => '30123456',
        ];

        if ($expectsAddress) {
            $payload['line_one'] = 'Av. Siempre Viva 742';
            $payload['city'] = 'Springfield';
            $payload['state'] = 'BUE';
            $payload['postcode'] = '1234';
        }

        $response = $this->postJson('/checkout/address', $payload);

        $response->assertStatus(200);

        $shipping = CartAddress::where('cart_id', $cart->id)
            ->where('type', 'shipping')
            ->first();

        $this->assertNotNull($shipping);
        $meta = (array) $shipping->meta;
        $this->assertSame('30123456', $meta['dni']);
        $this->assertSame('Ana', $shipping->first_name);
        $this->assertSame('García', $shipping->last_name);
    }

    #[DataProvider('receiverDeliveryTypeProvider')]
    public function test_receiver_domicilio_stores_address_fields(string $deliveryType, bool $expectsAddress): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);

        $payload = [
            'save_type' => 'receiver',
            'delivery_type' => $deliveryType,
            'first_name' => 'Ana',
            'last_name' => 'García',
            'dni' => '30123456',
            'line_one' => 'Av. Siempre Viva 742',
            'city' => 'Springfield',
            'state' => 'BUE',
            'postcode' => '1234',
        ];

        $response = $this->postJson('/checkout/address', $payload);

        $response->assertStatus(200);

        $shipping = CartAddress::where('cart_id', $cart->id)
            ->where('type', 'shipping')
            ->first();

        $this->assertNotNull($shipping);

        if ($expectsAddress) {
            $this->assertSame('Av. Siempre Viva 742', $shipping->line_one);
            $this->assertSame('Springfield', $shipping->city);
            $this->assertSame('BUE', $shipping->state);
            $this->assertSame('1234', $shipping->postcode);
        } else {
            // Non-domicilio: address should be dummy (Retiro en local, etc.)
            $this->assertNotSame('Av. Siempre Viva 742', $shipping->line_one);
        }
    }

    public static function receiverDeliveryTypeProvider(): array
    {
        return [
            'domicilio — address required' => ['domicilio', true],
            'retiro_local — no address' => ['retiro_local', false],
            'pickup_point — no address' => ['pickup_point', false],
        ];
    }

    // ─── Task 4.1: DNI validation ───────────────────────────────────────────────

    public function test_receiver_missing_dni_returns_422(): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);

        $response = $this->postJson('/checkout/address', [
            'save_type' => 'receiver',
            'delivery_type' => 'domicilio',
            'first_name' => 'Ana',
            'last_name' => 'García',
            'line_one' => 'Av. Siempre Viva 742',
            'city' => 'Springfield',
            'state' => 'BUE',
            'postcode' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dni']);
    }

    public function test_receiver_invalid_dni_returns_422(): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);

        $response = $this->postJson('/checkout/address', [
            'save_type' => 'receiver',
            'delivery_type' => 'retiro_local',
            'first_name' => 'Ana',
            'last_name' => 'García',
            'dni' => 'abc123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dni']);
    }

    public function test_receiver_dni_too_short_returns_422(): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);

        $response = $this->postJson('/checkout/address', [
            'save_type' => 'receiver',
            'delivery_type' => 'retiro_local',
            'first_name' => 'Ana',
            'last_name' => 'García',
            'dni' => '123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dni']);
    }

    // ─── Task 4.1: domicilio missing address fields → 422 ───────────────────────

    public function test_receiver_domicilio_missing_address_fields_returns_422(): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);

        $response = $this->postJson('/checkout/address', [
            'save_type' => 'receiver',
            'delivery_type' => 'domicilio',
            'first_name' => 'Ana',
            'last_name' => 'García',
            'dni' => '30123456',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['line_one', 'city', 'state', 'postcode']);
    }

    // ─── Receiver: missing delivery_type → 422 ───────────────────────────────────

    public function test_receiver_missing_delivery_type_returns_422(): void
    {
        $cart = $this->makeCart();

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart);

        $response = $this->postJson('/checkout/address', [
            'save_type' => 'receiver',
            'first_name' => 'Ana',
            'last_name' => 'García',
            'dni' => '30123456',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['delivery_type']);
    }

    // ─── Task 4.2: GET /checkout includes provincias, no billing ────────────────

    public function test_checkout_page_includes_provincias_prop(): void
    {
        $cart = $this->makeCart();

        Language::factory()->create(['default' => true]);

        $currency = Currency::where('code', 'ARS')->first();
        $variant = ProductVariant::factory()->create();
        Price::factory()->create([
            'priceable_type' => 'product_variant',
            'priceable_id' => $variant->id,
            'currency_id' => $currency->id,
        ]);
        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => 'product_variant',
            'purchasable_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cart->addresses()->where('type', 'shipping')->update(['shipping_option' => 'RETLOC']);

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart->refresh()->calculate());

        $response = $this->get('/checkout');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('provincias')
            ->where('provincias.CABA', 'Ciudad Autónoma de Buenos Aires'),
        );
    }

    public function test_checkout_page_has_no_billing_data(): void
    {
        $cart = $this->makeCart();

        Language::factory()->create(['default' => true]);

        $currency = Currency::where('code', 'ARS')->first();
        $variant = ProductVariant::factory()->create();
        Price::factory()->create([
            'priceable_type' => 'product_variant',
            'priceable_id' => $variant->id,
            'currency_id' => $currency->id,
        ]);
        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => 'product_variant',
            'purchasable_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cart->addresses()->where('type', 'shipping')->update(['shipping_option' => 'RETLOC']);

        $mock = $this->mock(CartSessionInterface::class);
        $mock->shouldReceive('current')->andReturn($cart->refresh()->calculate());

        $response = $this->get('/checkout');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('savedAddress.billing'),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    // ─── Helper ─────────────────────────────────────────────────────────────────

    private function makeCart(array $options = []): Cart
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
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        return $cart;
    }
}
