<?php

use App\Http\Controllers\TenantRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/cadastro', [TenantRegistrationController::class, 'create'])->name('cadastro');
Route::post('/cadastro', [TenantRegistrationController::class, 'store'])->name('cadastro.store');
Route::get('/cadastro/status/{tenant}', [TenantRegistrationController::class, 'status'])
    ->middleware('throttle:60,1')
    ->name('cadastro.status');
