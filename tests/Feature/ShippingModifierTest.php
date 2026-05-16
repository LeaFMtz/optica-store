<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modifiers\ShippingModifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\CartAddress;
use Lunar\Models\Channel;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\TaxClass;
use Tests\TestCase;

class ShippingModifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_retloc_option_is_always_added(): void
    {
        $cart = $this->makeCart();

        $modifier = new ShippingModifier;
        $modifier->handle($cart, fn ($c) => $c);

        $options = ShippingManifest::getOptions($cart);

        $this->assertTrue($options->contains(fn ($o) => $o->getIdentifier() === 'RETLOC'));
    }

    public function test_zipnova_option_is_re_registered_when_active_in_session(): void
    {
        $identifier = 'ZN_208_standard_delivery';

        session(['zipnova_quote_options' => [
            $identifier => [
                'identifier' => $identifier,
                'name' => 'OCA — Entrega a domicilio',
                'price' => 1058800,
                'currency' => 'ARS',
                'estimated_days' => '4–5 días',
                'logistic_type' => 'carrier_dropoff',
                'carrier_logo' => '',
            ],
        ]]);

        $cart = $this->makeCart($identifier);

        $modifier = new ShippingModifier;
        $modifier->handle($cart, fn ($c) => $c);

        $options = ShippingManifest::getOptions($cart);

        $this->assertTrue($options->contains(fn ($o) => $o->getIdentifier() === $identifier));
    }

    public function test_zipnova_option_price_is_correct(): void
    {
        $identifier = 'ZN_208_standard_delivery';

        session(['zipnova_quote_options' => [
            $identifier => [
                'identifier' => $identifier,
                'name' => 'OCA — Entrega a domicilio',
                'price' => 1058800,
                'currency' => 'ARS',
                'estimated_days' => '4–5 días',
                'logistic_type' => 'carrier_dropoff',
                'carrier_logo' => '',
            ],
        ]]);

        $cart = $this->makeCart($identifier);

        $modifier = new ShippingModifier;
        $modifier->handle($cart, fn ($c) => $c);

        $option = ShippingManifest::getOptions($cart)->first(fn ($o) => $o->getIdentifier() === $identifier);

        $this->assertNotNull($option);
        $this->assertEquals(1058800, $option->getPrice()->value);
    }

    public function test_zipnova_option_not_registered_when_not_in_session(): void
    {
        $identifier = 'ZN_208_standard_delivery';

        session(['zipnova_quote_options' => []]);

        $cart = $this->makeCart($identifier);

        $modifier = new ShippingModifier;
        $modifier->handle($cart, fn ($c) => $c);

        $options = ShippingManifest::getOptions($cart);

        $this->assertFalse($options->contains(fn ($o) => $o->getIdentifier() === $identifier));
    }

    public function test_no_zipnova_registered_when_active_option_is_retloc(): void
    {
        session(['zipnova_quote_options' => []]);

        $cart = $this->makeCart('RETLOC');

        $modifier = new ShippingModifier;
        $modifier->handle($cart, fn ($c) => $c);

        $options = ShippingManifest::getOptions($cart);
        $zipnovaOptions = $options->filter(fn ($o) => str_starts_with($o->getIdentifier(), 'ZN_'));

        $this->assertTrue($zipnovaOptions->isEmpty());
    }

    private function makeCart(?string $shippingOptionIdentifier = null): Cart
    {
        $channel = Channel::factory()->create(['default' => true]);
        $currency = Currency::factory()->create(['code' => 'ARS', 'decimal_places' => 2, 'default' => true]);
        TaxClass::factory()->create(['name' => 'Default', 'default' => true]);
        $country = Country::factory()->create();

        $cart = Cart::factory()->create([
            'currency_id' => $currency->id,
            'channel_id' => $channel->id,
        ]);

        CartAddress::factory()->create([
            'cart_id' => $cart->id,
            'country_id' => $country->id,
            'type' => 'shipping',
            'shipping_option' => $shippingOptionIdentifier,
        ]);

        return $cart->load('shippingAddress');
    }
}
