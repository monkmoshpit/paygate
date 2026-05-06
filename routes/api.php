<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;

Route::post('/payments', [Controllers\PaymentController::class, 'store']);
