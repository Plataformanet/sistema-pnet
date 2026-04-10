<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthTenantController;
use App\Http\Controllers\TenantClientController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantCustomerController;
use App\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/login', [AuthTenantController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/login', [AuthTenantController::class, 'login'])->name('tenant.login.submit');
    Route::get('/logout', [AuthTenantController::class, 'logout'])->name('tenant.logout');
    Route::get('/forgot-password', [AuthTenantController::class, 'showForgotPasswordForm'])->name('tenant.forgot-password');
    Route::get('/reset-password', [AuthTenantController::class, 'showResetPasswordForm'])->name('tenant.reset-password');

    Route::middleware(Authenticate::class)->group(function () {
        Route::get('/dashboard', [TenantController::class, 'dashboard'])->name('tenant.dashboard');
        Route::get('/registrations/clients/list', [TenantClientController::class, 'clientList'])->name('tenant.registrations.clients.list');
    });
});
