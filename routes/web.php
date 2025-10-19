<?php

use App\Http\Controllers\ChargeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('charges.create');
});

Route::prefix('charges')->name('charges.')->group(function () {
    Route::get('/create', [ChargeController::class, 'create'])->name('create');
    Route::post('/', [ChargeController::class, 'store'])->name('store');
    Route::get('/{txid}', [ChargeController::class, 'show'])->name('show');
    Route::get('/{txid}/status', [ChargeController::class, 'status'])->name('status');
});
