<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Gateways\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it finalizes as failed when gateway declines', function () {
    $payment = Payment::create([
        'user_id' => 1,
        'amount' => 50,
        'status' => PaymentStatus::PENDING,
        'idempotency_key' => 'gateway-decline',
    ]);

    $gateway = new class extends PaymentGateway {
        public function paymentCharge(int $userId, float $amount): bool
        {
            return false;
        }
    };

    $service = new class($gateway) extends PaymentService {
        protected function shouldLedgerFail(): bool
        {
            return false;
        }
    };

    $processed = $service->process($payment->fresh());

    expect($processed->fresh()->status)->toBe(PaymentStatus::FAILED);
});

test('it compensates gateway charge when ledger fails', function () {
    $payment = Payment::create([
        'user_id' => 2,
        'amount' => 99.99,
        'status' => PaymentStatus::PENDING,
        'idempotency_key' => 'ledger-fail',
    ]);

    $gateway = new class extends PaymentGateway {
        public int $refunds = 0;

        public function paymentCharge(int $userId, float $amount): bool
        {
            return true;
        }

        public function paymentRefund(int $userId, float $amount): bool
        {
            $this->refunds++;
            return true;
        }
    };

    $service = new class($gateway) extends PaymentService {
        protected function shouldLedgerFail(): bool
        {
            return true;
        }
    };

    try {
        $service->process($payment->fresh());
    } catch (\Exception $e) {
        // Expected from simulated ledger failure path.
    }

    expect($payment->fresh()->status)->toBe(PaymentStatus::FAILED);
    expect($gateway->refunds)->toBe(1);
});
