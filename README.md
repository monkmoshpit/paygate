# Paygate (Study Project)

Paygate is an API-only payment gateway sample designed for study purposes. It demonstrates request validation, idempotency handling, queued processing, saga-style orchestration, compensating actions, and structured logging.

## Scope and Intent

- This project is intentionally simulation-oriented.
- External gateway and ledger calls use random outcomes (`rand`) to reproduce success/failure paths.
- It is not intended for production payment processing.

## API Surface

### Create payment

- **Method:** `POST`
- **Path:** `/api/payments`
- **Body:**
  - `user_id` (required integer)
  - `amount` (required numeric, minimum `1`)
  - `idempotency_key` (required string)

On first request, a payment is created with `pending` status and queued for processing.
If the same `idempotency_key` is sent again, the existing payment is returned.

## Processing Lifecycle

Statuses use `App\Enums\PaymentStatus`:

- `pending`
- `processing`
- `success`
- `failed`

## Saga and Compensating Steps

`PaymentService::process()` executes explicit steps:

1. Move payment to `processing`.
2. Charge gateway.
3. Register ledger operation.
4. Finalize as `success`.

If an exception happens after a successful charge, compensation runs:

- Compensating step: gateway refund (`paymentRefund`).
- Payment is rolled back to `failed`.
- Error and compensation events are logged.

If the gateway charge is declined (`false`), payment is finalized as `failed` without compensation.

## API-only Configuration Rules

The application is configured to stay API-oriented:

- No web routes are registered.
- Local filesystem signed upload/download routes are disabled.
- Laravel Boost auto-discovery is disabled to avoid exposing development-only browser log routes.

## Running Locally

1. Install dependencies:
   - `composer install`
2. Environment:
   - copy `.env.example` to `.env`
   - `php artisan key:generate`
3. Database:
   - `php artisan migrate`
4. Run app:
   - `php artisan serve`
5. Run queue worker (for async processing):
   - `php artisan queue:work`

## Tests

- Run all tests with `php artisan test`.
- Feature tests cover endpoint validation and idempotency behavior.
- Unit tests cover payment service processing outcomes and compensation behavior.
