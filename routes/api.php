<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;

Route::post('/payments', [Controllers\PaymentController::class, 'store']);

Route::get('/', function () {
    return response()->json([
        'message' => 'Paygate API',
        'status' => 'healthy',
        'environment' => app()->environment(),
        'documentation' => [
            'endpoint' => '/api/payments',
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'payload_example' => [
                'user_id' => 1,
                'amount' => 150.00,
                'idempotency_key' => 'uuid-v4-or-unique-string'
            ]
        ],
        'github' => 'https://github.com/monkmoshpit/paygate'
    ], 200);
});
