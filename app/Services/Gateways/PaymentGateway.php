<?php

namespace App\Services\Gateways;

class PaymentGateway
{
    public function paymentCharge(int $userId, float $amount): bool
    {
        usleep(500000);

        // This simulates a call to an external payment gateway, which may fail for test purposes
        if (rand(1, 10) <= 3) {
            throw new \Exception("Gateway timeout");
        }
        return rand(0, 1);
    }

    public function paymentRefund(int $userId, float $amount): bool
    {
        usleep(250000);

        // This simulates a refund call to the external payment gateway.
        return true;
    }
}