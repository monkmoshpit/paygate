# Paygate

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square\&logo=php\&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square\&logo=laravel\&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)
![Status](https://img.shields.io/badge/status-study%20project-blue?style=flat-square)

> A robust, API-first payment gateway designed to simulate real-world transaction flows with idempotency, saga orchestration, and fault-tolerant processing.

---

## 📋 Overview

**Paygate** is a backend-focused project that models a payment processing system under realistic conditions.

The flow is built around **multi-step operations**, where each stage is executed independently and tracked throughout its lifecycle. The system handles partial failures, asynchronous execution, and state consistency across the entire process.

---

## 🚀 Key Features

* **Idempotent Payment Requests**
  Duplicate requests return the same result without reprocessing.

* **Saga-Based Processing Flow**
  Multi-step execution with explicit orchestration and recovery paths.

* **Compensating Transactions**
  Automatic rollback of external effects when failures occur mid-process.

* **Asynchronous Processing**
  Queue-based execution using background workers.

* **Structured Logging**
  Traceable flow across all processing steps.

* **Failure Simulation**
  Gateway and ledger operations simulate real-world instability.

---

## 🛠️ Technical Highlights

* **Backend**: Laravel 11, PHP 8.2+
* **Architecture**: Service Layer + Job Pipeline
* **Queue System**: Redis
* **Database**: MySQL / SQLite
* **Testing**: PHPUnit + Pest

---

## ⚙️ Processing Flow

```
POST /api/payments
        │
        ▼
Validate Request
        │
        ▼
Idempotency Check
        │
        ▼
Create Payment (pending)
        │
        ▼
Dispatch Async Job
        │
        ▼
Process Payment (Saga)
   ├─ Charge Gateway
   ├─ Register Ledger
   ├─ Finalize Success
   │
   └─ On Failure:
        ├─ Compensate (Refund)
        └─ Finalize Failed
```

---

## 🔄 Payment Lifecycle

```
pending → processing → success
                    ↘ failed
```

| Status       | Description                                     |
| ------------ | ----------------------------------------------- |
| `pending`    | Payment created and queued                      |
| `processing` | Worker executing steps                          |
| `success`    | Fully completed                                 |
| `failed`     | Execution failed (with or without compensation) |

---

## 🔁 Saga Flow

The payment processing is executed as a sequence of independent steps:

```
ProcessPaymentJob
   ↓
ChargeGatewayJob
   ↓
RegisterLedgerJob
```

Each step updates the payment state as it progresses.

If a failure occurs after the gateway charge succeeds, a compensation step is triggered:

```
Charge Gateway ──► success
Register Ledger ──► failure
        │
        ▼
Compensation:
  → Refund Gateway
  → Mark Payment as failed
```

If the gateway itself fails, no compensation is executed.

---

## 📡 API Reference

### Create Payment

```
POST /api/payments
```

| Field             | Type    | Required | Description            |
| ----------------- | ------- | -------- | ---------------------- |
| `user_id`         | integer | ✅        | Payment owner          |
| `amount`          | numeric | ✅        | Charge amount (min: 1) |
| `idempotency_key` | string  | ✅        | Unique request key     |

**201 — Created**

```json
{
  "id": 1,
  "user_id": 42,
  "amount": "150.00",
  "status": "pending",
  "idempotency_key": "order-abc-123"
}
```

**200 — Idempotent replay**

```json
{
  "id": 1,
  "user_id": 42,
  "amount": "150.00",
  "status": "success",
  "idempotency_key": "order-abc-123"
}
```

---

## ⚙️ Running Locally

**Prerequisites:** PHP 8.2+, Composer, Redis

```bash
git clone https://github.com/monkmoshpit/paygate.git
cd paygate

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

php artisan serve
php artisan queue:work
```

---

## 🧪 Tests

```bash
php artisan test
```

| Type    | Coverage                                      |
| ------- | --------------------------------------------- |
| Feature | API behavior, idempotency, status transitions |
| Unit    | Processing flow, compensation handling        |

---

## 👨‍💻 Technical Focus

This project demonstrates:

* Queue-driven processing
* Distributed flow handling
* Idempotent API design
* Failure and recovery handling

---

## 📄 License

MIT
