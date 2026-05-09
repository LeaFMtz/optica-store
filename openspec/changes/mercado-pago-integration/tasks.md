# Tasks: Integración Mercado Pago

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~450 (250 code + 200 test) |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1: Backend (config + driver + controller) → PR 2: Frontend + tests |
| Delivery strategy | ask-on-risk |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Config, driver, webhook controller, wiring | PR 1 | Base: main. Includes composer, env, config, MercadoPagoPayment, MercadoPagoController, AppServiceProvider, routes |
| 2 | Checkout flow, Vue, all tests | PR 2 | Base: PR 1. Modifies CheckoutController, CheckoutPlaceController, Index.vue + unit/feature tests |

## Phase 1: Infraestructura / Configuración

- [ ] 1.1 `composer.json`: Agregar `"mercadopago/dx-php": "^3.0"` → `composer update`
- [ ] 1.2 `.env.example`: Agregar `MERCADOPAGO_ACCESS_TOKEN`, `MERCADOPAGO_PUBLIC_KEY`, `MERCADOPAGO_WEBHOOK_SECRET`, `MERCADOPAGO_SANDBOX`
- [ ] 1.3 `config/services.php`: Bloque `mercadopago` con `access_token`, `public_key`, `webhook_secret`, `sandbox`
- [ ] 1.4 `config/lunar/orders.php`: Agregar statuses `payment-pending`, `payment-cancelled`, `payment-refunded`
- [ ] 1.5 `config/lunar/payments.php`: Agregar tipo `mercadopago` con `driver => 'mercadopago'`, `authorized => 'awaiting-payment'`

## Phase 2: Driver MercadoPagoPayment

- [ ] 2.1 Crear `app/PaymentTypes/MercadoPagoPayment.php`: Extiende `AbstractPayment`, constructor recibe `config('services.mercadopago')`
- [ ] 2.2 `authorize()`: Crea Order via `Cart::createOrder()`, crea MP Preference con items + payer + back_urls + external_reference, guarda Transaction con `meta.mp_redirect_url`, retorna `PaymentAuthorize(success:true)`
- [ ] 2.3 Sin token: Retorna `PaymentAuthorize(success:false, message:'MercadoPago no configurado')`
- [ ] 2.4 Error API MP: Captura excepción SDK → `PaymentAuthorize(success:false, message:'Error al crear preferencia: ...')`
- [ ] 2.5 `capture()` y `refund()`: Retornan `PaymentCapture(true)` / `PaymentRefund(true)` (Checkout Pro auto-captura)

## Phase 3: Webhook Controller

- [ ] 3.1 Crear `app/Http/Controllers/Webhooks/MercadoPagoController.php`: `__invoke(Request)`, CSRF exempt
- [ ] 3.2 Validación HMAC-SHA256: Parsear header `x-signature` → `hash_hmac('sha256', body, secret)` → `hash_equals` → 401 si inválido
- [ ] 3.3 Procesar tópico `payment`: Fetch payment vía SDK → mapear status → actualizar Order (status + `placed_at` en approved), idempotente
- [ ] 3.4 Tópicos desconocidos: `Log::info()`, HTTP 200

## Phase 4: AppServiceProvider + Routes

- [ ] 4.1 `app/Providers/AppServiceProvider.php`: En `boot()`, `Payments::extend('mercadopago', fn($app) => $app->make(MercadoPagoPayment::class))`
- [ ] 4.2 `routes/web.php`: `Route::post('/webhooks/mercadopago', MercadoPagoController::class)` fuera de CSRF (`withoutMiddleware`)

## Phase 5: Checkout Flow

- [ ] 5.1 `CheckoutController`: Pasar `paymentMethods` (keys de `config('lunar.payments.types')`) a la vista Inertia
- [ ] 5.2 `CheckoutPlaceController`: Aceptar `payment_type`, validar en `['cash-in-hand','mercadopago']`, rutear a `Payments::driver($type)`. Para MP: extraer `redirect_url` de Transaction.meta → incluirlo en JSON response
- [ ] 5.3 `resources/js/Pages/Checkout/Index.vue`: Recibir prop `paymentMethods`, renderizar radio buttons antes del botón Confirmar. En `placeOrder()`: si response tiene `redirect_url` → `window.location.href = redirect_url`

## Phase 6: Testing

- [ ] 6.1 `tests/Unit/MercadoPagoPaymentTest.php`: Test sin token → `success:false`; con `Http::fake()` → Order creada + Transaction con `redirect_url`
- [ ] 6.2 `tests/Unit/MercadoPagoControllerTest.php`: HMAC válido → 200, inválido → 401, idempotencia, status mapping (7 estados), tópico desconocido → 200
- [ ] 6.3 `tests/Feature/CheckoutPlaceTest.php`: POST con `mercadopago` → `redirect_url`; con `cash-in-hand` → `reference`; sin `payment_type` → 422; MP falla → error en response
- [ ] 6.4 `tests/Feature/WebhookTest.php`: Payload válido → Order.status actualizado; sin firma → 401; duplicado → idempotente

## Phase 7: Polish

- [ ] 7.1 `vendor/bin/pint --dirty --format agent`
- [ ] 7.2 `php artisan test --compact --filter=MercadoPago` — verificar todos los tests pasan
- [ ] 7.3 Ejecutar `php artisan test --compact` — verificar que tests existentes no se rompen
