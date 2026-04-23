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

        // Configurações - Usuários
        Route::get('/settings/users/list', [TenantUserController::class, 'userList'])->name('tenant.settings.users.list');
        Route::get('/settings/users/create', [TenantUserController::class, 'userCreate'])->name('tenant.settings.users.create');
        Route::get('/settings/users/{id}/edit', [TenantUserController::class, 'userEdit'])->name('tenant.settings.users.edit');
        Route::put('/settings/users/{id}', [TenantUserController::class, 'userUpdate'])->name('tenant.settings.users.update');

        // Services
        Route::get('/services/services/list', [App\Http\Controllers\TenantServiceController::class, 'serviceList'])->name('tenant.services.services.list');
        Route::get('/services/services/create', [App\Http\Controllers\TenantServiceController::class, 'serviceCreate'])->name('tenant.services.services.create');
        Route::get('/services/services/{id}/edit', [App\Http\Controllers\TenantServiceController::class, 'serviceEdit'])->name('tenant.services.services.edit');
        Route::put('/services/services/{id}', [App\Http\Controllers\TenantServiceController::class, 'serviceUpdate'])->name('tenant.services.services.update');

        // Service Categories
        Route::get('/services/categories/list', [App\Http\Controllers\TenantServiceCategoryController::class, 'categoryList'])->name('tenant.services.categories.list');
        Route::get('/services/categories/create', [App\Http\Controllers\TenantServiceCategoryController::class, 'categoryCreate'])->name('tenant.services.categories.create');
        Route::get('/services/categories/{id}/edit', [App\Http\Controllers\TenantServiceCategoryController::class, 'categoryEdit'])->name('tenant.services.categories.edit');
        Route::put('/services/categories/{id}', [App\Http\Controllers\TenantServiceCategoryController::class, 'categoryUpdate'])->name('tenant.services.categories.update');

        // Products
        Route::get('/products/products/list', [App\Http\Controllers\TenantProductController::class, 'productList'])->name('tenant.products.products.list');
        Route::get('/products/products/create', [App\Http\Controllers\TenantProductController::class, 'productCreate'])->name('tenant.products.products.create');
        Route::get('/products/products/{id}/edit', [App\Http\Controllers\TenantProductController::class, 'productEdit'])->name('tenant.products.products.edit');
        Route::put('/products/products/{id}', [App\Http\Controllers\TenantProductController::class, 'productUpdate'])->name('tenant.products.products.update');

        // Product Categories
        Route::get('/products/categories/list', [App\Http\Controllers\TenantProductCategoryController::class, 'categoryList'])->name('tenant.products.categories.list');
        Route::get('/products/categories/create', [App\Http\Controllers\TenantProductCategoryController::class, 'categoryCreate'])->name('tenant.products.categories.create');
        Route::get('/products/categories/{id}/edit', [App\Http\Controllers\TenantProductCategoryController::class, 'categoryEdit'])->name('tenant.products.categories.edit');
        Route::put('/products/categories/{id}', [App\Http\Controllers\TenantProductCategoryController::class, 'categoryUpdate'])->name('tenant.products.categories.update');

        // Configurações - Cargos (Roles)
        Route::get('/settings/roles/list', [App\Http\Controllers\TenantRoleController::class, 'roleList'])->name('tenant.settings.roles.list');
        Route::get('/settings/roles/create', [App\Http\Controllers\TenantRoleController::class, 'roleCreate'])->name('tenant.settings.roles.create');
        Route::get('/settings/roles/{id}/edit', [App\Http\Controllers\TenantRoleController::class, 'roleEdit'])->name('tenant.settings.roles.edit');
        Route::put('/settings/roles/{id}', [App\Http\Controllers\TenantRoleController::class, 'roleUpdate'])->name('tenant.settings.roles.update');

    });
});
