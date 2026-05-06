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
        Route::get('/registrations/clients/{id}/edit', [TenantClientController::class, 'edit'])->name('tenant.registrations.clients.edit')->middleware('permission:registrations.clients.update');
        Route::put('/registrations/clients/{id}', [TenantClientController::class, 'update'])->name('tenant.registrations.clients.update')->middleware('permission:registrations.clients.update');

        // Suppliers
        Route::get('/registrations/suppliers/list', [TenantSupplierController::class, 'index'])->name('tenant.registrations.suppliers.list')->middleware('permission:registrations.suppliers.view');
        Route::get('/registrations/suppliers/create', [TenantSupplierController::class, 'create'])->name('tenant.registrations.suppliers.create')->middleware('permission:registrations.suppliers.create');
        Route::get('/registrations/suppliers/{id}/edit', [TenantSupplierController::class, 'edit'])->name('tenant.registrations.suppliers.edit')->middleware('permission:registrations.suppliers.update');
        Route::put('/registrations/suppliers/{id}', [TenantSupplierController::class, 'update'])->name('tenant.registrations.suppliers.update')->middleware('permission:registrations.suppliers.update');

        // Employees
        Route::get('/registrations/employees/list', [TenantEmployeeController::class, 'index'])->name('tenant.registrations.employees.list')->middleware('permission:registrations.employees.view');
        Route::get('/registrations/employees/create', [TenantEmployeeController::class, 'create'])->name('tenant.registrations.employees.create')->middleware('permission:registrations.employees.create');
        Route::post('/registrations/employees/store', [TenantEmployeeController::class, 'store'])->name('tenant.registrations.employees.store')->middleware('permission:registrations.employees.create');
        Route::get('/registrations/employees/{id}/edit', [TenantEmployeeController::class, 'edit'])->name('tenant.registrations.employees.edit')->middleware('permission:registrations.employees.update');
        Route::put('/registrations/employees/{id}', [TenantEmployeeController::class, 'update'])->name('tenant.registrations.employees.update')->middleware('permission:registrations.employees.update');

        // Configurações - Usuários
        Route::get('/settings/users/list', [TenantUserController::class, 'userList'])->name('tenant.settings.users.list');
        Route::get('/settings/users/create', [TenantUserController::class, 'userCreate'])->name('tenant.settings.users.create');
        Route::get('/settings/users/{id}/edit', [TenantUserController::class, 'userEdit'])->name('tenant.settings.users.update');
        Route::put('/settings/users/{id}', [TenantUserController::class, 'userUpdate'])->name('tenant.settings.users.update');

        // Services
        Route::get('/services/services/list', [TenantServiceController::class, 'serviceList'])->name('tenant.services.services.list')->middleware('permission:services.services.view');
        Route::get('/services/services/create', [TenantServiceController::class, 'serviceCreate'])->name('tenant.services.services.create')->middleware('permission:services.services.create');
        Route::get('/services/services/{id}/edit', [TenantServiceController::class, 'serviceEdit'])->name('tenant.services.services.edit')->middleware('permission:services.services.update');
        Route::put('/services/services/{id}', [TenantServiceController::class, 'serviceUpdate'])->name('tenant.services.services.update')->middleware('permission:services.services.update');

        // Service Categories
        Route::get('/services/categories/list', [TenantServiceCategoryController::class, 'categoryList'])->name('tenant.services.categories.list')->middleware('permission:services.categories.view');
        Route::get('/services/categories/create', [TenantServiceCategoryController::class, 'categoryCreate'])->name('tenant.services.categories.create')->middleware('permission:services.categories.create');
        Route::get('/services/categories/{id}/edit', [TenantServiceCategoryController::class, 'categoryEdit'])->name('tenant.services.categories.edit')->middleware('permission:services.categories.update');
        Route::put('/services/categories/{id}', [TenantServiceCategoryController::class, 'categoryUpdate'])->name('tenant.services.categories.update')->middleware('permission:services.categories.update');

        // Products
        Route::get('/products/products/list', [TenantProductController::class, 'productList'])->name('tenant.products.products.list')->middleware('permission:products.products.view');
        Route::get('/products/products/create', [TenantProductController::class, 'productCreate'])->name('tenant.products.products.create')->middleware('permission:products.products.create');
        Route::get('/products/products/{id}/edit', [TenantProductController::class, 'productEdit'])->name('tenant.products.products.edit')->middleware('permission:products.products.update');
        Route::put('/products/products/{id}', [TenantProductController::class, 'productUpdate'])->name('tenant.products.products.update')->middleware('permission:products.products.update');

        // Product Categories
        Route::get('/products/categories/list', [TenantProductCategoryController::class, 'categoryList'])->name('tenant.products.categories.list')->middleware('permission:products.categories.view');
        Route::get('/products/categories/create', [TenantProductCategoryController::class, 'categoryCreate'])->name('tenant.products.categories.create')->middleware('permission:products.categories.create');
        Route::get('/products/categories/{id}/edit', [TenantProductCategoryController::class, 'categoryEdit'])->name('tenant.products.categories.edit')->middleware('permission:products.categories.update');
        Route::put('/products/categories/{id}', [TenantProductCategoryController::class, 'categoryUpdate'])->name('tenant.products.categories.update')->middleware('permission:products.categories.update');

        // Configurações - Cargos (Roles)
        Route::get('/settings/roles/list', [TenantRoleController::class, 'roleList'])->name('tenant.settings.roles.list');
        Route::get('/settings/roles/create', [TenantRoleController::class, 'roleCreate'])->name('tenant.settings.roles.create');
        Route::get('/settings/roles/{id}/edit', [TenantRoleController::class, 'roleEdit'])->name('tenant.settings.roles.edit');
        Route::put('/settings/roles/{id}', [TenantRoleController::class, 'roleUpdate'])->name('tenant.settings.roles.update');

    });
});
