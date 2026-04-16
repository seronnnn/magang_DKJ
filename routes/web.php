<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// ─── Root redirect ───────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('dashboard.index'));

// ─── Guest-only routes (redirect to dashboard if already logged in) ──────────
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});

// ─── Auth-protected routes ───────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/',             [DashboardController::class, 'index'])        ->name('index');
        Route::get('/aging',        [DashboardController::class, 'aging'])        ->name('aging');
        Route::get('/collection',   [DashboardController::class, 'collection'])   ->name('collection');
        Route::get('/overlimit',    [DashboardController::class, 'overlimit'])    ->name('overlimit');
        Route::get('/customers',    [DashboardController::class, 'customers'])    ->name('customers');
        Route::post('/collect',     [DashboardController::class, 'recordPayment'])->name('collect');
        Route::get('/aging-bucket', [DashboardController::class, 'agingBucket']) ->name('agingBucket');
        Route::get('/export',       [DashboardController::class, 'export'])       ->name('export');

        // Admin-only: inline row editing (controller enforces admin check)
        Route::put('/ar-data/{id}', [DashboardController::class, 'updateArData'])->name('updateArData');
    });
});