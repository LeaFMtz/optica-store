# Design: Integración Mercado Pago como Gateway de Pago Principal

## Technical Approach

Extender el sistema de drivers de Lunar con un `MercadoPagoPayment` registrado vía `Payments::extend()`. El flujo es redirect-based (Checkout Pro): `authorize()` crea orden + preference MP, guarda Transaction con `redirect_url` en `meta`, y el frontend redirige. La confirmación llega asíncrona por webhook con validación HMAC-SHA256. `cash-in-hand` se preserva como método alternativo sin cambios.

## Architecture Decisions

| Decisión | Opción | Alternativas | Razón |
|----------|--------|--------------|-------|
| Flujo de pago | Redirect (Checkout Pro) | Brick embebido, API directa | Checkout Pro es el producto estable de MP para Argentina, no requiere PCI-DSS |
| Inyección SDK | Constructor DI (`MercadoPago\Common\Manager`) | Resolver desde contenedor en `authorize()` | Pattern estándar Laravel; permite mock en tests |
| Redirect URL en DTO | `Transaction.meta.mp_redirect_url` | Modificar vendor DTO `PaymentAuthorize.message` | El DTO es vendor; `meta` es el campo canónico para datos extras en Lunar |
| Webhook idempotencia | Buscar Transaction por `reference` + `status` final → 200 OK sin cambios | Lock distribuido, cola de jobs | Suficiente para MVP; MP reenvía notificaciones |
| Orden en `authorize()` | `Cart::createOrder()` dentro del driver (igual que `OfflinePayment`) | Crear orden antes y pasar al driver | Sigue el contrato de Lunar; el pipeline de creación corre igual |
| Credenciales | `config/services.php.mercadopago` leídas desde `.env` | Hardcoded en driver | Security best practice; `services.php` es el lugar canónico |
| Estados nuevos | Agregar `payment-pending`, `payment-cancelled`, `payment-refunded` a `config/lunar/orders.php` | Usar solo estados existentes | Necesario para mapear MP statuses a estados de orden |

## Data Flow

```
Vue checkout (payment_type: "mercadopago")
  │ POST /checkout/place { payment_type }
  ▼
CheckoutPlaceController
  │ Payments::driver('mercadopago')->cart($cart)->withData([...])->authorize()
  ▼
MercadoPagoPayment::authorize()
  ├─ Cart::createOrder() → Order (status: awaiting-payment)
  ├─ MP SDK PreferenceClient::create(items, payer, back_urls, external_reference=order.reference)
  ├─ Transaction::create(type=intent, status=pending, reference=preference.id, meta={mp_redirect_url, mp_preference_id})
  └─ return PaymentAuthorize(success:true, orderId:$order->id)
  │
  ▼ Controller lee Transaction.meta.mp_redirect_url → responde JSON { redirect_url }
  │
  ▼ Frontend: window.location.href = redirect_url → MP Checkout Pro
  │
  ▼ [Usuario paga en MP]
  │
  ▼ POST /webhooks/mercadopago?data.id={payment_id}&type=payment
  │
  ▼ MercadoPagoController::__invoke()
  ├─ Validación HMAC-SHA256 (hash_equals) contra header x-signature → 401 si inválido
  ├─ GET /v1/payments/{payment_id} vía SDK (fuente de verdad)
  ├─ Busca Transaction por reference = external_reference → mapea status → Order.update()
  └─ 200 OK (idempotente si ya procesado)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/PaymentTypes/MercadoPagoPayment.php` | Create | Driver que extiende `AbstractPayment`. Constructor recibe `MercadoPago\Common\Manager` + credenciales de `config('services.mercadopago')`. |
| `app/Http/Controllers/Webhooks/MercadoPagoController.php` | Create | Handler POST `__invoke`. Valida HMAC-SHA256, consulta MP API por payment_id, mapea status → order status. Idempotente. |
| `app/Http/Controllers/CheckoutPlaceController.php` | Modify | Acepta `payment_type` del request. Usa `Payments::driver($type)->cart($cart)->authorize()`. Para MP: extrae `redirect_url` de la Transaction creada y la incluye en el JSON response. |
| `app/Http/Controllers/CheckoutController.php` | Modify | Pasa `config('lunar.payments.types')` transformado a la vista Inertia como `paymentMethods`. |
| `app/Providers/AppServiceProvider.php` | Modify | En `boot()`: `Payments::extend('mercadopago', fn($app) => $app->make(MercadoPagoPayment::class))`. |
| `config/lunar/payments.php` | Modify | Agregar tipo `mercadopago` con `driver => 'mercadopago'`, `authorized => 'awaiting-payment'`. |
| `config/lunar/orders.php` | Modify | Agregar statuses `payment-pending`, `payment-cancelled`, `payment-refunded`. |
| `config/services.php` | Modify | Bloque `mercadopago` con `access_token`, `public_key`, `webhook_secret`, `sandbox`. |
| `routes/web.php` | Modify | `Route::post('/webhooks/mercadopago', MercadoPagoController::class)` fuera de CSRF middleware. |
| `composer.json` | Modify | Agregar `mercadopago/dx-php: ^3.0`. |
| `.env.example` | Modify | `MERCADOPAGO_ACCESS_TOKEN=`, `MERCADOPAGO_PUBLIC_KEY=`, `MERCADOPAGO_SANDBOX=true`. |
| `resources/js/Pages/Checkout/Index.vue` | Modify | Recibir `paymentMethods` prop, renderizar radio buttons antes del botón confirmar. En `placeOrder()`: detectar `redirect_url` en response → `window.location.href = redirect_url`. Cash-in-hand mantiene `router.visit('/checkout/success')`. |

## Interfaces / Contracts

```php
// MercadoPagoPayment — firma (extiende AbstractPayment, implementa PaymentTypeInterface)
public function __construct(
    protected array $config,  // desde config('services.mercadopago')
) {}
public function authorize(): ?PaymentAuthorize;
// capture() y refund() delegan a MP API; Checkout Pro auto-captura → retornan PaymentCapture(true)

// MercadoPagoController — POST /webhooks/mercadopago (sin CSRF)
public function __invoke(Request $request): JsonResponse;
// Validación interna: parseHeader(x-signature) → hash_hmac('sha256', manifest, secret) → hash_equals

// Status mapping (constante en el controller)
const STATUS_MAP = [
    'approved' => 'payment-received',
    'pending' => 'payment-pending',
    'in_process' => 'payment-pending',
    'rejected' => 'payment-cancelled',
    'cancelled' => 'payment-cancelled',
    'refunded' => 'payment-refunded',
    'charged_back' => 'payment-cancelled',
];
```

## Error Handling

| Escenario | Manejo |
|-----------|--------|
| `MP_ACCESS_TOKEN` vacío | `authorize()` retorna `PaymentAuthorize(success:false, message:'MercadoPago no configurado')` |
| Preference API falla (timeout/4xx/5xx) | Capturar excepción SDK → `PaymentAuthorize(success:false, message:'Error al crear preferencia: ...')` |
| Webhook firma inválida | HTTP 401, `Log::warning()`. Sin state mutation. |
| Webhook `payment_id` no encontrado en MP API | HTTP 200, `Log::warning()`. Puede ser demora en replicación. |
| Webhook tópico desconocido | HTTP 200, `Log::info()`. |
| Webhook duplicado | Buscar Transaction con mismo `reference` + status ya final → 200 OK sin cambios |
| Order no encontrada por `external_reference` | HTTP 200, `Log::error('Order not found for reference: ...')` |

## Testing Strategy

| Capa | Qué probar | Enfoque |
|------|-----------|---------|
| Unit | `MercadoPagoPayment::authorize()` sin token | Instanciar manual con `config => []`. Verificar `PaymentAuthorize(success: false)`. |
| Unit | `MercadoPagoPayment::authorize()` con `Http::fake()` | Fake MP API responses. Verificar Order creada, Transaction con `meta.mp_redirect_url`, `PaymentAuthorize(success: true)`. |
| Unit | `MercadoPagoController` — validación HMAC | Generar firma válida con `hash_hmac` + secret conocido → 200; firma inválida → 401. |
| Unit | `MercadoPagoController` — idempotencia | Mismo payment_id procesado 2 veces → 200 ambas, sin transición duplicada. |
| Unit | Status mapping | Cada MP status mapea correctamente (tabla en spec). |
| Feature | `POST /checkout/place` con `mercadopago` | `Http::fake()` para MP API. Verificar JSON response incluye `redirect_url`. |
| Feature | `POST /checkout/place` con `cash-in-hand` | Verificar que no se rompe, response incluye `reference` sin `redirect_url`. |
| Feature | `POST /checkout/place` sin `payment_type` | HTTP 422 con error de validación. |
| Feature | `POST /webhooks/mercadopago` con payload válido | Verificar que Order.status se actualiza a `payment-received`. |
| Feature | `POST /webhooks/mercadopago` sin firma | HTTP 401. |

## Migration / Rollout

No requiere migración de datos. El cambio es aditivo: `cash-in-hand` sigue siendo default. Para activar MP: configurar credenciales en `.env` y setear `PAYMENTS_TYPE=mercadopago` (o mantener cash-in-hand como default y ofrecer ambos). Sin token configurado, el driver retorna error descriptivo.

## Open Questions

- [ ] ¿Incluimos `back_urls` (success/failure/pending) como rutas del storefront para retorno post-pago sin depender solo del webhook?
- [ ] ¿La preference necesita `payer.identification` (DNI) para Argentina o alcanza con `email`?
