<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthTenantController;
use App\Http\Controllers\TenantAccountPayableController;
use App\Http\Controllers\TenantAccountReceivableController;
use App\Http\Controllers\TenantBankAccountController;
use App\Http\Controllers\TenantBillingFlowController;
use App\Http\Controllers\TenantCashFlowController;
use App\Http\Controllers\TenantClientController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantDriveController;
use App\Http\Controllers\TenantDriveFolderController;
use App\Http\Controllers\TenantDriveLogController;
use App\Http\Controllers\TenantDriveSearchController;
use App\Http\Controllers\TenantDriveTrashController;
use App\Http\Controllers\TenantEmployeeController;
use App\Http\Controllers\TenantFinancialCategoryController;
use App\Http\Controllers\TenantFinancialSubcategoryController;
use App\Http\Controllers\TenantProductCategoryController;
use App\Http\Controllers\TenantProductController;
use App\Http\Controllers\TenantRoleController;
use App\Http\Controllers\TenantServiceCategoryController;
use App\Http\Controllers\TenantServiceController;
use App\Http\Controllers\TenantSpendingFlowController;
use App\Http\Controllers\TenantSupplierController;
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
        Route::get('/registrations/clients/get-contact-by-cpf-cnpj/{cpf_cnpj}', [TenantClientController::class, 'getContactByCpfCnpj'])->name('tenant.registrations.clients.get-contact-by-cpf-cnpj')->middleware('permission:registrations.clients.view');

        // Suppliers
        Route::get('/registrations/suppliers/list', [TenantSupplierController::class, 'index'])->name('tenant.registrations.suppliers.list')->middleware('permission:registrations.suppliers.view');
        Route::get('/registrations/suppliers/create', [TenantSupplierController::class, 'create'])->name('tenant.registrations.suppliers.create')->middleware('permission:registrations.suppliers.create');
        Route::post('/registrations/suppliers/store', [TenantSupplierController::class, 'store'])->name('tenant.registrations.suppliers.store')->middleware('permission:registrations.suppliers.create');
        Route::get('/registrations/suppliers/{id}/edit', [TenantSupplierController::class, 'edit'])->name('tenant.registrations.suppliers.edit')->middleware('permission:registrations.suppliers.edit');
        Route::put('/registrations/suppliers/{id}', [TenantSupplierController::class, 'update'])->name('tenant.registrations.suppliers.update')->middleware('permission:registrations.suppliers.edit');
        Route::delete('/registrations/suppliers/{id}', [TenantSupplierController::class, 'destroy'])->name('tenant.registrations.suppliers.destroy')->middleware('permission:registrations.suppliers.delete');
        Route::get('/registrations/suppliers/get-contact-by-cpf-cnpj/{cpf_cnpj}', [TenantSupplierController::class, 'getContactByCpfCnpj'])->name('tenant.registrations.suppliers.get-contact-by-cpf-cnpj')->middleware('permission:registrations.suppliers.view');

        // Employees
        Route::get('/registrations/employees/list', [TenantEmployeeController::class, 'index'])->name('tenant.registrations.employees.list')->middleware('permission:registrations.employees.view');
        Route::get('/registrations/employees/create', [TenantEmployeeController::class, 'create'])->name('tenant.registrations.employees.create')->middleware('permission:registrations.employees.create');
        Route::post('/registrations/employees/store', [TenantEmployeeController::class, 'store'])->name('tenant.registrations.employees.store')->middleware('permission:registrations.employees.create');
        Route::get('/registrations/employees/{id}/edit', [TenantEmployeeController::class, 'edit'])->name('tenant.registrations.employees.edit')->middleware('permission:registrations.employees.edit');
        Route::put('/registrations/employees/{id}', [TenantEmployeeController::class, 'update'])->name('tenant.registrations.employees.update')->middleware('permission:registrations.employees.edit');
        Route::delete('/registrations/employees/{id}', [TenantEmployeeController::class, 'destroy'])->name('tenant.registrations.employees.destroy')->middleware('permission:registrations.employees.delete');
        Route::get('/registrations/employees/get-contact-by-cpf-cnpj/{cpf_cnpj}', [TenantEmployeeController::class, 'getContactByCpfCnpj'])->name('tenant.registrations.employees.get-contact-by-cpf-cnpj')->middleware('permission:registrations.employees.view');

        // Configurações - Usuários
        Route::get('/settings/users/list', [TenantUserController::class, 'index'])->name('tenant.settings.users.list')->middleware('permission:settings.users.view');
        Route::get('/settings/users/create', [TenantUserController::class, 'create'])->name('tenant.settings.users.create')->middleware('permission:settings.users.create');
        Route::post('/settings/users/store', [TenantUserController::class, 'store'])->name('tenant.settings.users.store')->middleware('permission:settings.users.create');
        Route::get('/settings/users/{id}/edit', [TenantUserController::class, 'edit'])->name('tenant.settings.users.edit')->middleware('permission:settings.users.edit');
        Route::put('/settings/users/{id}', [TenantUserController::class, 'update'])->name('tenant.settings.users.update')->middleware('permission:settings.users.edit');
        Route::delete('/settings/users/{id}', [TenantUserController::class, 'delete'])->name('tenant.settings.users.destroy')->middleware('permission:settings.users.delete');

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

        // Finance Categories
        Route::get('/finance/categories/list', [TenantFinancialCategoryController::class, 'index'])->name('tenant.finance.categories.list')->middleware('permission:finance.categories.view');
        Route::get('/finance/categories/create', [TenantFinancialCategoryController::class, 'create'])->name('tenant.finance.categories.create')->middleware('permission:finance.categories.create');
        Route::post('/finance/categories/store', [TenantFinancialCategoryController::class, 'store'])->name('tenant.finance.categories.store')->middleware('permission:finance.categories.create');
        Route::get('/finance/categories/{id}/edit', [TenantFinancialCategoryController::class, 'edit'])->name('tenant.finance.categories.edit')->middleware('permission:finance.categories.edit');
        Route::put('/finance/categories/{id}', [TenantFinancialCategoryController::class, 'update'])->name('tenant.finance.categories.update')->middleware('permission:finance.categories.edit');
        Route::delete('/finance/categories/{id}', [TenantFinancialCategoryController::class, 'destroy'])->name('tenant.finance.categories.destroy')->middleware('permission:finance.categories.delete');

        // Finance Subcategories
        Route::get('/finance/subcategories/list', [TenantFinancialSubcategoryController::class, 'index'])->name('tenant.finance.subcategories.list')->middleware('permission:finance.subcategories.view');
        Route::get('/finance/subcategories/create', [TenantFinancialSubcategoryController::class, 'create'])->name('tenant.finance.subcategories.create')->middleware('permission:finance.subcategories.create');
        Route::post('/finance/subcategories/store', [TenantFinancialSubcategoryController::class, 'store'])->name('tenant.finance.subcategories.store')->middleware('permission:finance.subcategories.create');
        Route::get('/finance/subcategories/{id}/edit', [TenantFinancialSubcategoryController::class, 'edit'])->name('tenant.finance.subcategories.edit')->middleware('permission:finance.subcategories.edit');
        Route::put('/finance/subcategories/{id}', [TenantFinancialSubcategoryController::class, 'update'])->name('tenant.finance.subcategories.update')->middleware('permission:finance.subcategories.edit');
        Route::delete('/finance/subcategories/{id}', [TenantFinancialSubcategoryController::class, 'destroy'])->name('tenant.finance.subcategories.destroy')->middleware('permission:finance.subcategories.delete');

        // Contas Bancárias
        Route::get('/finance/bank-accounts/list', [TenantBankAccountController::class, 'index'])->name('tenant.finance.bank-accounts.list')->middleware('permission:finance.accounts.view');
        Route::get('/finance/bank-accounts/create', [TenantBankAccountController::class, 'create'])->name('tenant.finance.bank-accounts.create')->middleware('permission:finance.accounts.create');
        Route::post('/finance/bank-accounts/store', [TenantBankAccountController::class, 'store'])->name('tenant.finance.bank-accounts.store')->middleware('permission:finance.accounts.create');
        Route::get('/finance/bank-accounts/{id}/edit', [TenantBankAccountController::class, 'edit'])->name('tenant.finance.bank-accounts.edit')->middleware('permission:finance.accounts.edit');
        Route::put('/finance/bank-accounts/{id}', [TenantBankAccountController::class, 'update'])->name('tenant.finance.bank-accounts.update')->middleware('permission:finance.accounts.edit');
        Route::delete('/finance/bank-accounts/{id}', [TenantBankAccountController::class, 'destroy'])->name('tenant.finance.bank-accounts.destroy')->middleware('permission:finance.accounts.delete');

        // Contas a Pagar
        Route::get('/finance/accounts-payable/list', [TenantAccountPayableController::class, 'index'])->name('tenant.finance.accounts-payable.list')->middleware('permission:finance.accounts_payable.view');
        Route::get('/finance/accounts-payable/create', [TenantAccountPayableController::class, 'create'])->name('tenant.finance.accounts-payable.create')->middleware('permission:finance.accounts_payable.create');
        Route::post('/finance/accounts-payable/store', [TenantAccountPayableController::class, 'store'])->name('tenant.finance.accounts-payable.store')->middleware('permission:finance.accounts_payable.create');
        Route::get('/finance/accounts-payable/contacts', [TenantAccountPayableController::class, 'searchContact'])->name('tenant.finance.accounts-payable.search-contact')->middleware('permission:finance.accounts_payable.view');
        Route::get('/finance/accounts-payable/{id}', [TenantAccountPayableController::class, 'show'])->name('tenant.finance.accounts-payable.show')->middleware('permission:finance.accounts_payable.view');
        Route::get('/finance/accounts-payable/{id}/edit', [TenantAccountPayableController::class, 'edit'])->name('tenant.finance.accounts-payable.edit')->middleware('permission:finance.accounts_payable.edit');
        Route::put('/finance/accounts-payable/{id}', [TenantAccountPayableController::class, 'update'])->name('tenant.finance.accounts-payable.update')->middleware('permission:finance.accounts_payable.edit');
        Route::delete('/finance/accounts-payable/{id}', [TenantAccountPayableController::class, 'destroy'])->name('tenant.finance.accounts-payable.destroy')->middleware('permission:finance.accounts_payable.delete');
        Route::patch('/finance/accounts-payable/installments/update', [TenantAccountPayableController::class, 'updateInstallments'])->name('tenant.finance.accounts-payable.installments.update')->middleware('permission:finance.accounts_payable.edit');
        Route::patch('/finance/accounts-payable/installments/value', [TenantAccountPayableController::class, 'updateInstallmentValue'])->name('tenant.finance.accounts-payable.installments.value')->middleware('permission:finance.accounts_payable.edit');

        // Contas a Receber
        Route::get('/finance/accounts-receivable/list', [TenantAccountReceivableController::class, 'index'])->name('tenant.finance.accounts-receivable.list')->middleware('permission:finance.accounts_receivable.view');
        Route::get('/finance/accounts-receivable/create', [TenantAccountReceivableController::class, 'create'])->name('tenant.finance.accounts-receivable.create')->middleware('permission:finance.accounts_receivable.create');
        Route::post('/finance/accounts-receivable/store', [TenantAccountReceivableController::class, 'store'])->name('tenant.finance.accounts-receivable.store')->middleware('permission:finance.accounts_receivable.create');
        Route::get('/finance/accounts-receivable/contacts', [TenantAccountReceivableController::class, 'searchContact'])->name('tenant.finance.accounts-receivable.search-contact')->middleware('permission:finance.accounts_receivable.view');
        Route::get('/finance/accounts-receivable/{id}', [TenantAccountReceivableController::class, 'show'])->name('tenant.finance.accounts-receivable.show')->middleware('permission:finance.accounts_receivable.view');
        Route::get('/finance/accounts-receivable/{id}/edit', [TenantAccountReceivableController::class, 'edit'])->name('tenant.finance.accounts-receivable.edit')->middleware('permission:finance.accounts_receivable.edit');
        Route::put('/finance/accounts-receivable/{id}', [TenantAccountReceivableController::class, 'update'])->name('tenant.finance.accounts-receivable.update')->middleware('permission:finance.accounts_receivable.edit');
        Route::delete('/finance/accounts-receivable/{id}', [TenantAccountReceivableController::class, 'destroy'])->name('tenant.finance.accounts-receivable.destroy')->middleware('permission:finance.accounts_receivable.delete');
        Route::patch('/finance/accounts-receivable/installments/update', [TenantAccountReceivableController::class, 'updateInstallments'])->name('tenant.finance.accounts-receivable.installments.update')->middleware('permission:finance.accounts_receivable.edit');
        Route::patch('/finance/accounts-receivable/installments/value', [TenantAccountReceivableController::class, 'updateInstallmentValue'])->name('tenant.finance.accounts-receivable.installments.value')->middleware('permission:finance.accounts_receivable.edit');

        // Fluxo de Caixa
        Route::get('/finance/cash-flow', TenantCashFlowController::class)->name('tenant.finance.cash-flow.index')->middleware('permission:finance.cash_flow.view');

        // Fluxo de Gastos
        Route::get('/finance/spending-flow', [TenantSpendingFlowController::class, 'index'])->name('tenant.finance.spending-flow.index')->middleware('permission:finance.spending_flow.view');
        Route::get('/finance/spending-flow/pdf', [TenantSpendingFlowController::class, 'geraPDF'])->name('tenant.finance.spending-flow.pdf')->middleware('permission:finance.spending_flow.view');

        // Faturamentos
        Route::get('/finance/billing', [TenantBillingFlowController::class, 'index'])->name('tenant.finance.billing.index')->middleware('permission:finance.billing.view');

        // Configurações - Cargos (Roles)
        Route::get('/settings/roles/list', [TenantRoleController::class, 'index'])->name('tenant.settings.roles.list')->middleware('permission:settings.roles.view');
        Route::get('/settings/roles/create', [TenantRoleController::class, 'create'])->name('tenant.settings.roles.create')->middleware('permission:settings.roles.create');
        Route::post('/settings/roles/store', [TenantRoleController::class, 'store'])->name('tenant.settings.roles.store')->middleware('permission:settings.roles.create');
        Route::get('/settings/roles/{id}/edit', [TenantRoleController::class, 'edit'])->name('tenant.settings.roles.edit')->middleware('permission:settings.roles.edit');
        Route::put('/settings/roles/{id}', [TenantRoleController::class, 'update'])->name('tenant.settings.roles.update')->middleware('permission:settings.roles.edit');
        Route::delete('/settings/roles/{id}', [TenantRoleController::class, 'destroy'])->name('tenant.settings.roles.destroy')->middleware('permission:settings.roles.delete');

        // Drive - Arquivos
        Route::get('/drive', [TenantDriveController::class, 'index'])->name('tenant.drive.index')->middleware('permission:drive.drives.view');
        Route::get('/drive/search', TenantDriveSearchController::class)->name('tenant.drive.search')->middleware('permission:drive.drives.view');
        Route::get('/drive/logs', TenantDriveLogController::class)->name('tenant.drive.logs')->middleware('permission:drive.logs.view');
        Route::get('/drive/folders/list', [TenantDriveController::class, 'listFolders'])->name('tenant.drive.folders.list')->middleware('permission:drive.drives.view');
        Route::get('/drive/{id}/download', [TenantDriveController::class, 'download'])->name('tenant.drive.download')->whereNumber('id')->middleware('permission:drive.drives.view');
        Route::post('/drive', [TenantDriveController::class, 'store'])->name('tenant.drive.store')->middleware('permission:drive.drives.create');
        Route::put('/drive', [TenantDriveController::class, 'update'])->name('tenant.drive.update')->middleware('permission:drive.drives.edit');
        Route::delete('/drive/selected', [TenantDriveController::class, 'deleteSelected'])->name('tenant.drive.delete-selected')->middleware('permission:drive.drives.delete');
        Route::delete('/drive/{id}', [TenantDriveController::class, 'destroy'])->name('tenant.drive.destroy')->whereNumber('id')->middleware('permission:drive.drives.delete');
        Route::post('/drive/move', [TenantDriveController::class, 'move'])->name('tenant.drive.move')->middleware('permission:drive.drives.edit');

        // Drive - Permissões de acesso
        Route::get('/drive/users', [TenantDriveController::class, 'shareableUsers'])->name('tenant.drive.users')->middleware('permission:drive.drives.view');
        Route::post('/drive/permissions', [TenantDriveController::class, 'storeAccessPermissions'])->name('tenant.drive.permissions.store')->middleware('permission:drive.drives.create');
        Route::get('/drive/{id}/permissions', [TenantDriveController::class, 'userAccess'])->name('tenant.drive.permissions.users')->whereNumber('id')->middleware('permission:drive.drives.view');
        Route::delete('/drive/{drive_id}/permissions/{user_id}', [TenantDriveController::class, 'removeUserAccess'])->name('tenant.drive.permissions.remove')->whereNumber(['drive_id', 'user_id'])->middleware('permission:drive.drives.delete');

        // Drive - Pastas
        Route::get('/folders', [TenantDriveFolderController::class, 'index'])->name('tenant.drive.folders.index')->middleware('permission:drive.folders.view');
        Route::get('/folders/create', [TenantDriveFolderController::class, 'create'])->name('tenant.drive.folders.create')->middleware('permission:drive.folders.create');
        Route::post('/folders', [TenantDriveFolderController::class, 'store'])->name('tenant.drive.folders.store')->middleware('permission:drive.folders.create');
        Route::delete('/folders/{id}', [TenantDriveFolderController::class, 'destroy'])->name('tenant.drive.folders.destroy')->whereNumber('id')->middleware('permission:drive.folders.delete');

        // Drive - Lixeira
        Route::get('/trash', [TenantDriveTrashController::class, 'index'])->name('tenant.drive.trash.index')->middleware('permission:drive.trash.view');
        Route::post('/trash/restore', [TenantDriveTrashController::class, 'restore'])->name('tenant.drive.trash.restore')->middleware('permission:drive.trash.edit');
        Route::delete('/trash', [TenantDriveTrashController::class, 'destroy'])->name('tenant.drive.trash.force-delete')->middleware('permission:drive.trash.delete');
        Route::post('/trash/clear', [TenantDriveTrashController::class, 'clearTrash'])->name('tenant.drive.trash.clear')->middleware('permission:drive.trash.delete');

    });
});
