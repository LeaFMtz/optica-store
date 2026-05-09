# Payment Driver Spec

## Requirements

### MercadoPagoPayment Driver

The system MUST provide `App\PaymentTypes\MercadoPagoPayment` extending `Lunar\PaymentTypes\AbstractPayment`, registered via `Payments::extend('mercadopago', ...)` in `AppServiceProvider`. It MUST use `mercadopago/dx-php` v2+ with credentials from `MP_ACCESS_TOKEN` and `MP_PUBLIC_KEY`. It MUST implement `authorize()`, `capture()`, `refund()`.

#### Scenario: Authorize creates preference

- GIVEN a cart with items, shipping, and billing
- WHEN `authorize()` is called
- THEN a MP preference is created via SDK with cart lines, payer email, external_reference
- AND a `Transaction` (type: intent, status: pending) is stored
- AND `PaymentAuthorize` returns `success=true`, `message` containing `redirectUrl`

#### Scenario: Missing credentials

- GIVEN `MP_ACCESS_TOKEN` is empty or invalid
- WHEN `authorize()` is called
- THEN `PaymentAuthorize` returns `success=false` with error message

#### Scenario: Capture / Refund

- GIVEN a Transaction with stored MP `payment_id`
- WHEN `capture()` is called → `PaymentCapture(success: true)` (auto-captured by MP Checkout Pro)
- WHEN `refund()` is called → MP refund API called, `PaymentRefund` reflects API result
