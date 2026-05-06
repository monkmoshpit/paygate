<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use App\Services\Gateways\PaymentGateway;
use App\Jobs\ProcessPaymentJob;

class PaymentService
{
    public function __construct(
        private PaymentGateway $gateway
    ) {}

    public function create(array $data): Payment
    {
        $existingPayment = Payment::where('idempotency_key', $data['idempotency_key'])->first();
        if ($existingPayment) {
            return $existingPayment;
        }

        $payment = Payment::create([
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'status' => PaymentStatus::PENDING,
            'idempotency_key' => $data['idempotency_key'],
        ]);

        ProcessPaymentJob::dispatch($payment);

        Log::info('payment.processing.started', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
        ]);

        return $payment;
    }

    public function process(Payment $payment): Payment
    {
        $this->processingStatus($payment);

        try {
            $steps = [];

            $gatewayAccepted = $this->chargeGateway($payment, $steps);

            if (! $gatewayAccepted) {
                return $this->finalize($payment, false);
            }

            $this->registerLedger($payment);

            return $this->finalize($payment, true);

        } catch (\Exception $e) {
            if (isset($steps)) {
                $this->compensate($payment, $steps, $e);
            }

            $this->rollback($payment, $e->getMessage());
            
            Log::error('payment.processing.error', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param array<int, string> $steps
     */
    private function chargeGateway(Payment $payment, array &$steps): bool
    {
        Log::info('payment.gateway.request', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);

        $success = $this->gateway->paymentCharge(
            $payment->user_id,
            $payment->amount
        );

        Log::info('payment.gateway.response', [
            'payment_id' => $payment->id,
            'success' => $success,
        ]);

        if ($success) {
            $steps[] = 'gateway_charged';
        }

        return $success;
    }

    private function registerLedger(Payment $payment): void
    {
        // This simulates a call to an external ledger service, which may fail for test purposes
        if ($this->shouldLedgerFail()) {
            throw new \Exception("Ledger failed");
        }
    }

    protected function shouldLedgerFail(): bool
    {
        return rand(1, 10) <= 3;
    }

    private function processingStatus(Payment $payment): void
    {
        $payment->status = PaymentStatus::PROCESSING;
        $payment->save();
    }

    private function finalize(Payment $payment, bool $success): Payment
    {
        $payment->status = $success
            ? PaymentStatus::SUCCESS
            : PaymentStatus::FAILED;

        $payment->save();

        return $payment;
    }

    private function rollback(Payment $payment, string $reason): void
    {
        $payment->status = PaymentStatus::FAILED;
        $payment->save();

        Log::warning('payment.rollback', [
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]);
    }

    /**
     * @param array<int, string> $steps
     */
    private function compensate(Payment $payment, array $steps, \Throwable $error): void
    {
        while ($step = array_pop($steps)) {
            if ($step === 'gateway_charged') {
                $this->compensateGatewayCharge($payment, $error);
            }
        }
    }

    private function compensateGatewayCharge(Payment $payment, \Throwable $error): void
    {
        $refunded = $this->gateway->paymentRefund($payment->user_id, $payment->amount);

        Log::warning('payment.saga.compensation.gateway_refund', [
            'payment_id' => $payment->id,
            'refunded' => $refunded,
            'reason' => $error->getMessage(),
        ]);
    }
}