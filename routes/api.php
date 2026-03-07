<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// ============================================================
// Public Routes (No Auth Required)
// ============================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ============================================================
// Protected Routes (Sanctum Auth Required)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/topup', [WalletController::class, 'topup']);
    Route::post('/transfer', [WalletController::class, 'transfer']);

    // Transactions
    Route::get('/transactions', [WalletController::class, 'transactions']);
});
