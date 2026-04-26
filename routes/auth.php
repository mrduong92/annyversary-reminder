<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\OtpController;
use Illuminate\Support\Facades\Route;

// ── Google OAuth (auth_driver = 'google') ────────────────────
Route::middleware('guest')->group(function () {
    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// ── OTP phone auth (auth_driver = 'otp') ─────────────────────
Route::middleware('guest')->group(function () {
    Route::get('register', [OtpController::class, 'showRegister'])->name('register');
    Route::post('register', [OtpController::class, 'sendRegisterOtp']);

    Route::get('login', [OtpController::class, 'showLogin'])->name('login');
    Route::post('login', [OtpController::class, 'sendLoginOtp']);

    Route::get('otp/verify', [OtpController::class, 'showVerify'])->name('otp.verify');
    Route::post('otp/verify', [OtpController::class, 'verify'])->name('otp.verify.submit');
});

// ── Shared ────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('logout', [OtpController::class, 'logout'])->name('logout');
});
