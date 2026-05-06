<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentService;
use App\Http\Requests\StorePaymentRequest;

class PaymentController extends Controller
{
    
    public function store(StorePaymentRequest $request, PaymentService $service)
    {
        $payment = $service->create($request->validated());

        return response()->json($payment);
    }
}