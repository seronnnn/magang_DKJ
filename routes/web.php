<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard.index'));

Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/',           [DashboardController::class, 'index'])       ->name('index');
    Route::get('/aging',      [DashboardController::class, 'aging'])       ->name('aging');
    Route::get('/collection', [DashboardController::class, 'collection'])  ->name('collection');
    Route::get('/overlimit',  [DashboardController::class, 'overlimit'])   ->name('overlimit');
    Route::get('/customers',  [DashboardController::class, 'customers'])   ->name('customers');
    Route::post('/collect',   [DashboardController::class, 'recordPayment'])->name('collect');
    Route::get('/aging-bucket', [DashboardController::class, 'agingBucket'])->name('agingBucket');
    Route::get('/export',     [DashboardController::class, 'export'])      ->name('export');
});