# Payment Checkout Spec

## Requirements

### Multi-Method Checkout

`CheckoutController` MUST pass `config('lunar.payments.types')` keys to the Inertia view. Frontend MUST render one radio/button per type.

`CheckoutPlaceController` MUST accept `payment_type`, route to `Payments::driver($type)->cart($cart)->authorize()`. For MP: redirect to `redirectUrl`. For cash-in-hand: redirect to `/checkout/success`.

#### Scenario: User pays with MercadoPago

- GIVEN complete cart on confirm step
- WHEN user selects "mercadopago" and submits
- THEN `authorize()` returns `success: true, redirectUrl`
- AND frontend navigates to MP Checkout Pro

#### Scenario: User pays with cash-in-hand

- GIVEN complete cart on confirm step
- WHEN user selects "cash-in-hand" and submits
- THEN existing `OfflinePayment` flow completes, redirects to success

#### Scenario: No method selected → validation error 422

#### Scenario: MP preference creation fails → error shown, no redirect
