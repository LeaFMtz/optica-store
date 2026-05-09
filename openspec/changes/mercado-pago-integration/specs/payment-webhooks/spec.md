# Payment Webhooks Spec

## Requirements

### Webhook Endpoint

The system MUST expose `POST /webhooks/mercadopago` (CSRF exempt) mapped to `App\Http\Controllers\Webhooks\MercadoPagoController`.

### Signature Validation

Every webhook MUST be validated via HMAC-SHA256 of raw body using `MP_WEBHOOK_SECRET` from `.env`, checked against `x-signature` header. Invalid → HTTP 401, no processing.

### Topic Processing

The controller MUST fetch authoritative state from MP API by `payment_id` — webhook body is informational only.

| Topic | Action |
|-------|--------|
| `payment` | Fetch full payment → map status → update order |
| `merchant_order` | Log only |
| Unknown | Log, return 200 |

#### Scenario: Valid payment webhook

- GIVEN valid `x-signature`, topic=`payment`, `payment_id=123`
- WHEN webhook received
- THEN SDK fetches `/v1/payments/123`, order status updated, HTTP 200

#### Scenario: Invalid signature → HTTP 401, no processing

#### Scenario: Unknown topic → HTTP 200, logged only

#### Scenario: Duplicate notification → idempotent, no double transition
