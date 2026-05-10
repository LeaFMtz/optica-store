<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lunar\Facades\CartSession;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Country;
use Lunar\Models\ProductVariant;

class CheckoutController extends Controller
{
    private function hasInsufficientStock(iterable $lines): bool
    {
        foreach ($lines as $line) {
            if ($line->purchasable_type !== 'product_variant') {
                continue;
            }

            $variant = ProductVariant::find($line->purchasable_id);

            if ($variant && !$variant->canBeFulfilledAtQuantity($line->quantity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Show the checkout page.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $cart = CartSession::current();

        if (!$cart || $cart->lines->isEmpty()) {
            return redirect()->route('home');
        }

        if ($this->hasInsufficientStock($cart->lines)) {
            return redirect()->route('home')->with('error', 'Uno o más productos ya no tienen stock suficiente.');
        }

        $options = ShippingManifest::getOptions($cart);

        $hasDeliveryShipping = $options->contains(fn ($o) => !$o->collect);

        $shippingOptions = $options->map(fn ($option) => [
            'identifier' => $option->getIdentifier(),
            'name' => $option->getName(),
            'description' => $option->getDescription(),
            'price' => $option->getPrice()->formatted(),
            'collect' => $option->collect,
        ])->values()->all();

        $countries = Country::orderBy('name')->get()
            ->sortByDesc(fn ($c) => $c->iso3 === 'ARG')
            ->map(fn ($country) => [
                'id' => $country->id,
                'name' => $country->native,
            ])->values()->all();

        $savedAddress = null;

        if ($shippingAddress = $cart->shippingAddress) {
            $savedAddress = [
                'first_name' => $shippingAddress->first_name,
                'last_name' => $shippingAddress->last_name,
                'company_name' => $shippingAddress->company_name,
                'line_one' => $shippingAddress->line_one,
                'line_two' => $shippingAddress->line_two,
                'line_three' => $shippingAddress->line_three,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'postcode' => $shippingAddress->postcode,
                'country_id' => $shippingAddress->country_id,
                'contact_email' => $shippingAddress->contact_email,
                'contact_phone' => $shippingAddress->contact_phone,
                'delivery_instructions' => $shippingAddress->delivery_instructions,
            ];
        }

        $cartData = [
            'lines' => $cart->lines->map(fn ($line) => [
                'id' => $line->id,
                'description' => $line->purchasable->getDescription(),
                'quantity' => $line->quantity,
                'sub_total' => $line->subTotal->formatted(),
                'unit_price' => $line->unitPrice->formatted(),
                'thumbnail' => $line->purchasable->getThumbnail()?->getUrl(),
            ])->values()->all(),
            'sub_total' => $cart->subTotal->formatted(),
            'total' => $cart->total->formatted(),
            'total_raw' => $cart->total->value / 100,  // float in ARS for MP card form / installments
            'tax_breakdown' => $cart->taxBreakdown->amounts->map(fn ($tax) => [
                'description' => $tax->description,
                'price' => $tax->price->formatted(),
            ])->values()->all(),
            'shipping_option' => $cart->shippingAddress?->shipping_option
                ? ShippingManifest::getOption($cart, $cart->shippingAddress->shipping_option)?->getName()
                : null,
        ];

        return Inertia::render('Checkout/Index', [
            'cart' => $cartData,
            'shippingOptions' => $shippingOptions,
            'savedAddress' => $savedAddress,
            'countries' => $countries,
            'hasDeliveryShipping' => $hasDeliveryShipping,
        ]);
    }
}
