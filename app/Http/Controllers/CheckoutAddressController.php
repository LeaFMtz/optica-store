<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Lunar\Facades\CartSession;
use Lunar\Models\Country;

class CheckoutAddressController extends Controller
{
    /**
     * Save the shipping address for the current cart.
     *
     * save_type values:
     *   'contact'  — Step 1: contact data (name, email, phone)
     *   'receiver' — Step 3: receiver data (DNI + name, conditional address)
     */
    public function __invoke(Request $request): JsonResponse
    {
        $cart = CartSession::current();

        if (!$cart) {
            return response()->json(['message' => 'No active cart.'], 422);
        }

        $saveType = $request->input('save_type', 'receiver');

        // ─── Step 1: Contact ────────────────────────────────────────
        if ($saveType === 'contact') {
            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'contact_email' => ['required', 'email', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'max:255'],
            ]);

            $argentina = Country::where('iso3', 'ARG')->value('id') ?? 1;

            $addressData = array_merge($validated, [
                'line_one' => 'Retiro en local',
                'city' => 'Local',
                'postcode' => '0000',
                'country_id' => $argentina,
            ]);

            $cart->setShippingAddress($addressData);

            return response()->json(['message' => 'Contact saved.']);
        }

        // ─── Step 2: Receiver (always: DNI + name, conditional address) ──
        $baseRules = [
            'delivery_type' => ['required', 'string', Rule::in(['domicilio', 'retiro_local', 'pickup_point'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'string', 'regex:/^\d{7,8}$/'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
        ];

        $isDomicilio = $request->input('delivery_type') === 'domicilio';

        if ($isDomicilio) {
            $baseRules['line_one'] = ['required', 'string', 'max:255'];
            $baseRules['city'] = ['required', 'string', 'max:255'];
            $baseRules['state'] = ['required', 'string', 'max:255'];
            $baseRules['postcode'] = ['required', 'string', 'max:10'];
        } else {
            $baseRules['line_one'] = ['nullable', 'string', 'max:255'];
            $baseRules['line_two'] = ['nullable', 'string', 'max:255'];
            $baseRules['city'] = ['nullable', 'string', 'max:255'];
            $baseRules['state'] = ['nullable', 'string', 'max:255'];
            $baseRules['postcode'] = ['nullable', 'string', 'max:10'];
        }

        $validated = $request->validate($baseRules);

        $argentina = Country::where('iso3', 'ARG')->value('id') ?? 1;

        $addressData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'line_one' => $isDomicilio ? $validated['line_one'] : 'Retiro en local',
            'city' => $isDomicilio ? $validated['city'] : 'Local',
            'state' => $isDomicilio ? ($validated['state'] ?? '') : '',
            'postcode' => $isDomicilio ? $validated['postcode'] : '0000',
            'country_id' => $argentina,
            'contact_email' => $validated['contact_email'] ?? 'placeholder@example.com',
            'contact_phone' => $validated['contact_phone'] ?? '',
            'shipping_option' => $cart->shippingAddress?->shipping_option,
            'meta' => [
                'dni' => $validated['dni'],
            ],
        ];

        $cart->refresh();

        $cart->setShippingAddress($addressData);
        $cart->setBillingAddress($addressData);

        return response()->json(['message' => 'Receiver saved.']);
    }
}
