<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthTenantController;
use App\Http\Controllers\TenantClientController;
use App\Http\Controllers\TenantProductCategoryController;
use App\Http\Controllers\TenantProductController;
use App\Http\Controllers\TenantRoleController;
use App\Http\Controllers\TenantServiceCategoryController;
use App\Http\Controllers\TenantServiceController;
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
        Route::get('/registrations/clients/list', [TenantClientController::class, 'index'])->name('tenant.registrations.clients.list')->middleware('permission:registrations.clients.view');
        Route::get('/registrations/clients/create', [TenantClientController::class, 'create'])->name('tenant.registrations.clients.create')->middleware('permission:registrations.clients.create');
        Route::post('/registrations/clients/store', [TenantClientController::class, 'store'])->name('tenant.registrations.clients.store')->middleware('permission:registrations.clients.create');
        Route::get('/registrations/clients/{id}/edit', [TenantClientController::class, 'edit'])->name('tenant.registrations.clients.edit')->middleware('permission:registrations.clients.edit');
        Route::put('/registrations/clients/{id}', [TenantClientController::class, 'update'])->name('tenant.registrations.clients.update')->middleware('permission:registrations.clients.edit');
        Route::delete('/registrations/clients/{id}', [TenantClientController::class, 'destroy'])->name('tenant.registrations.clients.destroy')->middleware('permission:registrations.clients.delete');

        // Suppliers
        Route::get('/registrations/suppliers/list', [TenantSupplierController::class, 'index'])->name('tenant.registrations.suppliers.list')->middleware('permission:registrations.suppliers.view');
        Route::get('/registrations/suppliers/create', [TenantSupplierController::class, 'create'])->name('tenant.registrations.suppliers.create')->middleware('permission:registrations.suppliers.create');
        Route::post('/registrations/suppliers/store', [TenantSupplierController::class, 'store'])->name('tenant.registrations.suppliers.store')->middleware('permission:registrations.suppliers.create');
        Route::get('/registrations/suppliers/{id}/edit', [TenantSupplierController::class, 'edit'])->name('tenant.registrations.suppliers.edit')->middleware('permission:registrations.suppliers.edit');
        Route::put('/registrations/suppliers/{id}', [TenantSupplierController::class, 'update'])->name('tenant.registrations.suppliers.update')->middleware('permission:registrations.suppliers.edit');
        Route::delete('/registrations/suppliers/{id}', [TenantSupplierController::class, 'destroy'])->name('tenant.registrations.suppliers.destroy')->middleware('permission:registrations.suppliers.delete');

        // Employees
        Route::get('/registrations/employees/list', [TenantEmployeeController::class, 'index'])->name('tenant.registrations.employees.list')->middleware('permission:registrations.employees.view');
        Route::get('/registrations/employees/create', [TenantEmployeeController::class, 'create'])->name('tenant.registrations.employees.create')->middleware('permission:registrations.employees.create');
        Route::post('/registrations/employees/store', [TenantEmployeeController::class, 'store'])->name('tenant.registrations.employees.store')->middleware('permission:registrations.employees.create');
        Route::get('/registrations/employees/{id}/edit', [TenantEmployeeController::class, 'edit'])->name('tenant.registrations.employees.edit')->middleware('permission:registrations.employees.edit');
        Route::put('/registrations/employees/{id}', [TenantEmployeeController::class, 'update'])->name('tenant.registrations.employees.update')->middleware('permission:registrations.employees.edit');
        Route::delete('/registrations/employees/{id}', [TenantEmployeeController::class, 'destroy'])->name('tenant.registrations.employees.destroy')->middleware('permission:registrations.employees.delete');

        // Configurações - Usuários
        Route::get('/settings/users/list', [TenantUserController::class, 'index'])->name('tenant.settings.users.list');
        Route::get('/settings/users/create', [TenantUserController::class, 'create'])->name('tenant.settings.users.create');
        Route::post('/settings/users/store', [TenantUserController::class, 'store'])->name('tenant.settings.users.store');
        Route::get('/settings/users/{id}/edit', [TenantUserController::class, 'edit'])->name('tenant.settings.users.edit');
        Route::put('/settings/users/{id}', [TenantUserController::class, 'update'])->name('tenant.settings.users.update');

        // Services
        Route::get('/services/services/list', [TenantServiceController::class, 'index'])->name('tenant.services.services.list')->middleware('permission:services.services.view');
        Route::get('/services/services/create', [TenantServiceController::class, 'create'])->name('tenant.services.services.create')->middleware('permission:services.services.create');
        Route::post('/services/services/store', [TenantServiceController::class, 'store'])->name('tenant.services.services.store')->middleware('permission:services.services.create');
        Route::get('/services/services/{id}/edit', [TenantServiceController::class, 'edit'])->name('tenant.services.services.edit')->middleware('permission:services.services.edit');
        Route::put('/services/services/{id}', [TenantServiceController::class, 'update'])->name('tenant.services.services.update')->middleware('permission:services.services.edit');
        Route::delete('/services/services/{id}', [TenantServiceController::class, 'destroy'])->name('tenant.services.services.destroy')->middleware('permission:services.services.delete');

        // Service Categories
        Route::get('/services/categories/list', [TenantServiceCategoryController::class, 'index'])->name('tenant.services.categories.list')->middleware('permission:services.categories.view');
        Route::get('/services/categories/create', [TenantServiceCategoryController::class, 'create'])->name('tenant.services.categories.create')->middleware('permission:services.categories.create');
        Route::post('/services/categories/store', [TenantServiceCategoryController::class, 'store'])->name('tenant.services.categories.store')->middleware('permission:services.categories.create');
        Route::get('/services/categories/{id}/edit', [TenantServiceCategoryController::class, 'edit'])->name('tenant.services.categories.edit')->middleware('permission:services.categories.edit');
        Route::put('/services/categories/{id}', [TenantServiceCategoryController::class, 'update'])->name('tenant.services.categories.update')->middleware('permission:services.categories.edit');
        Route::delete('/services/categories/{id}', [TenantServiceCategoryController::class, 'destroy'])->name('tenant.services.categories.destroy')->middleware('permission:services.categories.delete');

        // Products
        Route::get('/products/products/list', [TenantProductController::class, 'index'])->name('tenant.products.products.list')->middleware('permission:products.products.view');
        Route::get('/products/products/create', [TenantProductController::class, 'create'])->name('tenant.products.products.create')->middleware('permission:products.products.create');
        Route::post('/products/products/store', [TenantProductController::class, 'store'])->name('tenant.products.products.store')->middleware('permission:products.products.create');
        Route::get('/products/products/{id}/edit', [TenantProductController::class, 'edit'])->name('tenant.products.products.edit')->middleware('permission:products.products.edit');
        Route::put('/products/products/{id}', [TenantProductController::class, 'update'])->name('tenant.products.products.update')->middleware('permission:products.products.edit');
        Route::delete('/products/products/{id}', [TenantProductController::class, 'destroy'])->name('tenant.products.products.delete')->middleware('permission:products.products.delete');

        // Product Categories
        Route::get('/products/categories/list', [TenantProductCategoryController::class, 'index'])->name('tenant.products.categories.list')->middleware('permission:products.categories.view');
        Route::get('/products/categories/create', [TenantProductCategoryController::class, 'create'])->name('tenant.products.categories.create')->middleware('permission:products.categories.create');
        Route::post('/products/categories/store', [TenantProductCategoryController::class, 'store'])->name('tenant.products.categories.store')->middleware('permission:products.categories.create');
        Route::get('/products/categories/{id}/edit', [TenantProductCategoryController::class, 'edit'])->name('tenant.products.categories.edit')->middleware('permission:products.categories.edit');
        Route::put('/products/categories/{id}', [TenantProductCategoryController::class, 'update'])->name('tenant.products.categories.update')->middleware('permission:products.categories.edit');
        Route::delete('/products/categories/{id}', [TenantProductCategoryController::class, 'destroy'])->name('tenant.products.categories.destroy')->middleware('permission:products.categories.delete');

        // Configurações - Cargos (Roles)
        Route::get('/settings/roles/list', [TenantRoleController::class, 'index'])->name('tenant.settings.roles.list');
        Route::get('/settings/roles/create', [TenantRoleController::class, 'create'])->name('tenant.settings.roles.create');
        Route::post('/settings/roles/store', [TenantRoleController::class, 'store'])->name('tenant.settings.roles.store');
        Route::get('/settings/roles/{id}/edit', [TenantRoleController::class, 'edit'])->name('tenant.settings.roles.edit');
        Route::put('/settings/roles/{id}', [TenantRoleController::class, 'update'])->name('tenant.settings.roles.update');
        Route::delete('/settings/roles/{id}', [TenantRoleController::class, 'destroy'])->name('tenant.settings.roles.destroy');

    });
});
