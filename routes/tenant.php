<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthTenantController;
use App\Http\Controllers\TenantClientController;
use App\Http\Controllers\TenantSupplierController;
use App\Http\Controllers\TenantEmployeeController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantUserController;
use App\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
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

        // Clients
        Route::get('/registrations/clients/list', [TenantClientController::class, 'clientList'])->name('tenant.registrations.clients.list');
        Route::get('/registrations/clients/create', [TenantClientController::class, 'clientCreate'])->name('tenant.registrations.clients.create');
        Route::get('/registrations/clients/{id}/edit', [TenantClientController::class, 'clientEdit'])->name('tenant.registrations.clients.edit');
        Route::put('/registrations/clients/{id}', [TenantClientController::class, 'clientUpdate'])->name('tenant.registrations.clients.update');

        // Suppliers
        Route::get('/registrations/suppliers/list', [TenantSupplierController::class, 'supplierList'])->name('tenant.registrations.suppliers.list');
        Route::get('/registrations/suppliers/create', [TenantSupplierController::class, 'supplierCreate'])->name('tenant.registrations.suppliers.create');
        Route::get('/registrations/suppliers/{id}/edit', [TenantSupplierController::class, 'supplierEdit'])->name('tenant.registrations.suppliers.edit');
        Route::put('/registrations/suppliers/{id}', [TenantSupplierController::class, 'supplierUpdate'])->name('tenant.registrations.suppliers.update');

        // Employees
        Route::get('/registrations/employees/list', [TenantEmployeeController::class, 'employeeList'])->name('tenant.registrations.employees.list');
        Route::get('/registrations/employees/create', [TenantEmployeeController::class, 'employeeCreate'])->name('tenant.registrations.employees.create');
        Route::get('/registrations/employees/{id}/edit', [TenantEmployeeController::class, 'employeeEdit'])->name('tenant.registrations.employees.edit');
        Route::put('/registrations/employees/{id}', [TenantEmployeeController::class, 'employeeUpdate'])->name('tenant.registrations.employees.update');

        // Users
        Route::get('/registrations/users/list', [TenantUserController::class, 'userList'])->name('tenant.registrations.users.list');
        Route::get('/registrations/users/create', [TenantUserController::class, 'userCreate'])->name('tenant.registrations.users.create');
        Route::get('/registrations/users/{id}/edit', [TenantUserController::class, 'userEdit'])->name('tenant.registrations.users.edit');
        Route::put('/registrations/users/{id}', [TenantUserController::class, 'userUpdate'])->name('tenant.registrations.users.update');
    });
});
