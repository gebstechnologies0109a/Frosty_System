<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Store\KiloPurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('store')->name('store.')->group(function () {
    Route::get('/kilos', [KiloPurchaseController::class, 'create'])->name('kilos.create');
    Route::post('/kilos', [KiloPurchaseController::class, 'store'])->name('kilos.store');
});
