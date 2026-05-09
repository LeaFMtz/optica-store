# Payment Status Spec

## Requirements

### Status Mapping

The webhook controller MUST map MP payment statuses to Lunar order statuses:

| MP Status | Order Status |
|-----------|-------------|
| `approved` | `payment-received` |
| `pending` | `payment-pending` |
| `in_process` | `payment-pending` |
| `rejected` | `payment-cancelled` |
| `cancelled` | `payment-cancelled` |
| `refunded` | `payment-refunded` |
| `charged_back` | `payment-cancelled` |
| Unknown | `payment-pending` (default) |

On `approved`: set `placed_at = now()`.

### Sandbox Manual Testing

When `MERCADOPAGO_SANDBOX=true`, testers MUST verify the full cycle with MP test cards (any CVV, any future expiry, name=`APRO`):

| Test Card | Expected |
|-----------|----------|
| Visa 5031 7557 3453 0604 | Approved → `payment-received` |
| Amex 3739 5391 1029 1073 | Rejected → `payment-cancelled` |
| Expired card | Rejected |

#### Scenario: Approved sandbox flow → webhook sets `payment-received`

#### Scenario: Rejected sandbox flow → webhook sets `payment-cancelled`

#### Scenario: Post-approval state → `placed_at` is set

#### Scenario: Post-rejection state → order transitions to `payment-cancelled`
