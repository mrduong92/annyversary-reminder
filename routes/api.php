<?php

use App\Http\Controllers\Webhook\SepayController;
use Illuminate\Support\Facades\Route;

// SePay webhook — không cần auth, xác thực bằng token trong header
Route::post('/webhook/sepay', [SepayController::class, 'handle'])->name('webhook.sepay');
