<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthTenantController;
use App\Http\Controllers\TenantAccountPayableController;
use App\Http\Controllers\TenantAccountReceivableController;
use App\Http\Controllers\TenantApplicantLookupController;
use App\Http\Controllers\TenantBankAccountController;
use App\Http\Controllers\TenantBankController;
use App\Http\Controllers\TenantBillableServiceController;
use App\Http\Controllers\TenantBillingFlowController;
use App\Http\Controllers\TenantCashFlowController;
use App\Http\Controllers\TenantClientController;
use App\Http\Controllers\TenantCompanySettingController;
use App\Http\Controllers\TenantContractTypeController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantConvertQuoteToProposalController;
use App\Http\Controllers\TenantCostTypeController;
use App\Http\Controllers\TenantCrmController;
use App\Http\Controllers\TenantDevelopmentController;
use App\Http\Controllers\TenantDriveController;
use App\Http\Controllers\TenantDriveFolderController;
use App\Http\Controllers\TenantDriveLogController;
use App\Http\Controllers\TenantDriveSearchController;
use App\Http\Controllers\TenantDriveTrashController;
use App\Http\Controllers\TenantEmployeeController;
use App\Http\Controllers\TenantFeeCalculationController;
use App\Http\Controllers\TenantFeeCalculatorController;
use App\Http\Controllers\TenantFinancialCategoryController;
use App\Http\Controllers\TenantFinancialSubcategoryController;
use App\Http\Controllers\TenantItbiBracketController;
use App\Http\Controllers\TenantItbiMunicipalityController;
use App\Http\Controllers\TenantItbiRateController;
use App\Http\Controllers\TenantMunicipalityLookupController;
use App\Http\Controllers\TenantNotaryController;
use App\Http\Controllers\TenantProductCategoryController;
use App\Http\Controllers\TenantProductController;
use App\Http\Controllers\TenantProfileController;
use App\Http\Controllers\TenantPropertyTypeController;
use App\Http\Controllers\TenantProposalApplicantController;
use App\Http\Controllers\TenantProposalController;
use App\Http\Controllers\TenantProposalCostItemController;
use App\Http\Controllers\TenantProposalDocumentController;
use App\Http\Controllers\TenantProposalFeeEstimateController;
use App\Http\Controllers\TenantProposalPdfController;
use App\Http\Controllers\TenantProposalSearchController;
use App\Http\Controllers\TenantProposalSellerController;
use App\Http\Controllers\TenantProposalTimelineController;
use App\Http\Controllers\TenantQuoteController;
use App\Http\Controllers\TenantQuotePdfController;
use App\Http\Controllers\TenantReceiptController;
use App\Http\Controllers\TenantRoleController;
use App\Http\Controllers\TenantSendQuoteEmailController;
use App\Http\Controllers\TenantServiceCategoryController;
use App\Http\Controllers\TenantServiceController;
use App\Http\Controllers\TenantSpendingFlowController;
use App\Http\Controllers\TenantStageController;
use App\Http\Controllers\TenantSupplierController;
use App\Http\Controllers\TenantUserController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectProposalRestrictedUsers;
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
    Route::post('/password/email', [AuthTenantController::class, 'sendResetLink'])->name('tenant.password.email')->middleware('throttle:password-reset');
    Route::post('/password/reset', [AuthTenantController::class, 'resetPassword'])->name('tenant.password.update')->middleware('throttle:password-reset');
    Route::get('/settings/company/logo', [TenantCompanySettingController::class, 'showLogo'])->name('tenant.settings.company.logo');

    Route::middleware(Authenticate::class)->group(function () {
        Route::middleware(RedirectProposalRestrictedUsers::class)->group(function () {
            Route::get('/dashboard', [TenantController::class, 'dashboard'])->name('tenant.dashboard');

            // CRM (Prototipo Visual)
            Route::get('/crm/kanban', [TenantCrmController::class, 'kanban'])->name('tenant.crm.kanban');
            Route::get('/crm/list', [TenantCrmController::class, 'list'])->name('tenant.crm.list');
        });

        // Clients
        Route::get('/registrations/clients/list', [TenantClientController::class, 'index'])->name('tenant.registrations.clients.list')->middleware('permission:registrations.clients.view');
        Route::get('/registrations/clients/create', [TenantClientController::class, 'create'])->name('tenant.registrations.clients.create')->middleware('permission:registrations.clients.create');
        Route::post('/registrations/clients/store', [TenantClientController::class, 'store'])->name('tenant.registrations.clients.store')->middleware('permission:registrations.clients.create');
        Route::get('/registrations/clients/{id}/edit', [TenantClientController::class, 'edit'])->name('tenant.registrations.clients.edit')->middleware('permission:registrations.clients.edit');
        Route::put('/registrations/clients/{id}', [TenantClientController::class, 'update'])->name('tenant.registrations.clients.update')->middleware('permission:registrations.clients.edit');
        Route::delete('/registrations/clients/{id}', [TenantClientController::class, 'destroy'])->name('tenant.registrations.clients.destroy')->middleware('permission:registrations.clients.delete');
        Route::patch('/registrations/clients/{id}/toggle-active', [TenantClientController::class, 'toggleActive'])->name('tenant.registrations.clients.toggle-active')->middleware('permission:registrations.clients.edit');
        Route::get('/registrations/clients/get-contact-by-cpf-cnpj/{cpf_cnpj}', [TenantClientController::class, 'getContactByCpfCnpj'])->name('tenant.registrations.clients.get-contact-by-cpf-cnpj')->middleware('permission:registrations.clients.view');

        // Suppliers
        Route::get('/registrations/suppliers/list', [TenantSupplierController::class, 'index'])->name('tenant.registrations.suppliers.list')->middleware('permission:registrations.suppliers.view');
        Route::get('/registrations/suppliers/create', [TenantSupplierController::class, 'create'])->name('tenant.registrations.suppliers.create')->middleware('permission:registrations.suppliers.create');
        Route::post('/registrations/suppliers/store', [TenantSupplierController::class, 'store'])->name('tenant.registrations.suppliers.store')->middleware('permission:registrations.suppliers.create');
        Route::get('/registrations/suppliers/{id}/edit', [TenantSupplierController::class, 'edit'])->name('tenant.registrations.suppliers.edit')->middleware('permission:registrations.suppliers.edit');
        Route::put('/registrations/suppliers/{id}', [TenantSupplierController::class, 'update'])->name('tenant.registrations.suppliers.update')->middleware('permission:registrations.suppliers.edit');
        Route::delete('/registrations/suppliers/{id}', [TenantSupplierController::class, 'destroy'])->name('tenant.registrations.suppliers.destroy')->middleware('permission:registrations.suppliers.delete');
        Route::patch('/registrations/suppliers/{id}/toggle-active', [TenantSupplierController::class, 'toggleActive'])->name('tenant.registrations.suppliers.toggle-active')->middleware('permission:registrations.suppliers.edit');
        Route::get('/registrations/suppliers/get-contact-by-cpf-cnpj/{cpf_cnpj}', [TenantSupplierController::class, 'getContactByCpfCnpj'])->name('tenant.registrations.suppliers.get-contact-by-cpf-cnpj')->middleware('permission:registrations.suppliers.view');

        // Employees
        Route::get('/registrations/employees/list', [TenantEmployeeController::class, 'index'])->name('tenant.registrations.employees.list')->middleware('permission:registrations.employees.view');
        Route::get('/registrations/employees/create', [TenantEmployeeController::class, 'create'])->name('tenant.registrations.employees.create')->middleware('permission:registrations.employees.create');
        Route::post('/registrations/employees/store', [TenantEmployeeController::class, 'store'])->name('tenant.registrations.employees.store')->middleware('permission:registrations.employees.create');
        Route::get('/registrations/employees/{id}/edit', [TenantEmployeeController::class, 'edit'])->name('tenant.registrations.employees.edit')->middleware('permission:registrations.employees.edit');
        Route::put('/registrations/employees/{id}', [TenantEmployeeController::class, 'update'])->name('tenant.registrations.employees.update')->middleware('permission:registrations.employees.edit');
        Route::delete('/registrations/employees/{id}', [TenantEmployeeController::class, 'destroy'])->name('tenant.registrations.employees.destroy')->middleware('permission:registrations.employees.delete');
        Route::patch('/registrations/employees/{id}/toggle-active', [TenantEmployeeController::class, 'toggleActive'])->name('tenant.registrations.employees.toggle-active')->middleware('permission:registrations.employees.edit');
        Route::get('/registrations/employees/get-contact-by-cpf-cnpj/{cpf_cnpj}', [TenantEmployeeController::class, 'getContactByCpfCnpj'])->name('tenant.registrations.employees.get-contact-by-cpf-cnpj')->middleware('permission:registrations.employees.view');

        // Perfil do Usuário
        Route::get('/profile', [TenantProfileController::class, 'edit'])->name('tenant.profile.edit');
        Route::post('/profile', [TenantProfileController::class, 'updateProfile'])->name('tenant.profile.update');
        Route::get('/profile/avatar', [TenantProfileController::class, 'showAvatar'])->name('tenant.profile.avatar');
        Route::put('/profile/password', [TenantProfileController::class, 'updatePassword'])->name('tenant.profile.password.update');

        // Configurações - Empresa
        Route::get('/settings/company', [TenantCompanySettingController::class, 'edit'])->name('tenant.settings.company.edit')->middleware('permission:settings.company.view');
        Route::post('/settings/company', [TenantCompanySettingController::class, 'update'])->name('tenant.settings.company.update')->middleware('permission:settings.company.edit');

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

        // Documentações - Propostas
        Route::get('/documents/proposals/list', [TenantProposalController::class, 'index'])->name('tenant.documents.proposals.list')->middleware('permission:documents.proposals.view');
        Route::get('/documents/proposals/create', [TenantProposalController::class, 'create'])->name('tenant.documents.proposals.create')->middleware('permission:documents.proposals.create');
        Route::post('/documents/proposals/store', [TenantProposalController::class, 'store'])->name('tenant.documents.proposals.store')->middleware('permission:documents.proposals.create');
        Route::get('/documents/proposals/pdf/list', [TenantProposalPdfController::class, 'list'])->name('tenant.documents.proposals.pdf.list')->middleware('permission:documents.proposals.view');
        Route::get('/documents/applicants/lookup', TenantApplicantLookupController::class)->name('tenant.documents.applicants.lookup')->middleware(['permission:documents.proposals.create', 'throttle:documents-lookup']);
        Route::get('/documents/proposals/{id}', [TenantProposalController::class, 'show'])->name('tenant.documents.proposals.show')->whereNumber('id')->middleware('permission:documents.proposals.view');
        Route::get('/documents/proposals/{id}/edit', [TenantProposalController::class, 'edit'])->name('tenant.documents.proposals.edit')->whereNumber('id')->middleware('permission:documents.proposals.view');
        Route::put('/documents/proposals/{id}', [TenantProposalController::class, 'update'])->name('tenant.documents.proposals.update')->whereNumber('id')->middleware('permission:documents.proposals.edit');
        Route::patch('/documents/proposals/{id}/particularities', [TenantProposalController::class, 'updateParticularities'])->name('tenant.documents.proposals.particularities.update')->whereNumber('id')->middleware('permission:documents.proposals.edit');
        Route::post('/documents/proposals/{id}/applicants', [TenantProposalApplicantController::class, 'store'])->name('tenant.documents.proposals.applicants.store')->whereNumber('id')->middleware('permission:documents.proposals.edit');
        Route::put('/documents/proposals/{id}/applicants/{applicantId}', [TenantProposalApplicantController::class, 'update'])->name('tenant.documents.proposals.applicants.update')->whereNumber(['id', 'applicantId'])->middleware('permission:documents.proposals.edit');
        Route::delete('/documents/proposals/{id}/applicants/{applicantId}', [TenantProposalApplicantController::class, 'destroy'])->name('tenant.documents.proposals.applicants.destroy')->whereNumber(['id', 'applicantId'])->middleware('permission:documents.proposals.edit');
        Route::post('/documents/proposals/{id}/sellers', [TenantProposalSellerController::class, 'store'])->name('tenant.documents.proposals.sellers.store')->whereNumber('id')->middleware('permission:documents.proposals.edit');
        Route::put('/documents/proposals/{id}/sellers/{sellerId}', [TenantProposalSellerController::class, 'update'])->name('tenant.documents.proposals.sellers.update')->whereNumber(['id', 'sellerId'])->middleware('permission:documents.proposals.edit');
        Route::delete('/documents/proposals/{id}/sellers/{sellerId}', [TenantProposalSellerController::class, 'destroy'])->name('tenant.documents.proposals.sellers.destroy')->whereNumber(['id', 'sellerId'])->middleware('permission:documents.proposals.edit');
        Route::delete('/documents/proposals/{id}', [TenantProposalController::class, 'destroy'])->name('tenant.documents.proposals.destroy')->whereNumber('id')->middleware('permission:documents.proposals.delete');
        Route::get('/documents/proposals/{id}/pdf/info', [TenantProposalPdfController::class, 'info'])->name('tenant.documents.proposals.pdf.info')->whereNumber('id')->middleware('permission:documents.proposals.view');
        Route::get('/documents/proposals/{id}/pdf/tracking', [TenantProposalPdfController::class, 'tracking'])->name('tenant.documents.proposals.pdf.tracking')->whereNumber('id')->middleware('permission:documents.proposals.view');

        // Documentações - Acompanhamento da proposta
        Route::post('/documents/proposals/{id}/timeline/start', [TenantProposalTimelineController::class, 'start'])->name('tenant.documents.proposals.timeline.start')->whereNumber('id')->middleware('permission:documents.proposal_timeline.edit');
        Route::post('/documents/proposals/{id}/timeline/{proposalStageId}/complete', [TenantProposalTimelineController::class, 'complete'])->name('tenant.documents.proposals.timeline.complete')->whereNumber(['id', 'proposalStageId'])->middleware('permission:documents.proposal_timeline.edit');
        Route::post('/documents/proposals/{id}/timeline/restore', [TenantProposalTimelineController::class, 'restore'])->name('tenant.documents.proposals.timeline.restore')->whereNumber('id')->middleware('permission:documents.proposal_timeline.delete');

        // Documentações - Documentos da proposta
        Route::post('/documents/proposals/{id}/documents', [TenantProposalDocumentController::class, 'store'])->name('tenant.documents.proposals.documents.store')->whereNumber('id')->middleware('permission:documents.proposal_documents.create');
        Route::get('/documents/proposals/{id}/documents/{documentId}/download', [TenantProposalDocumentController::class, 'download'])->name('tenant.documents.proposals.documents.download')->whereNumber(['id', 'documentId'])->middleware('permission:documents.proposal_documents.view');
        Route::delete('/documents/proposals/{id}/documents/{documentId}', [TenantProposalDocumentController::class, 'destroy'])->name('tenant.documents.proposals.documents.destroy')->whereNumber(['id', 'documentId'])->middleware('permission:documents.proposal_documents.delete');

        // Documentações - Pagamentos, taxas e recibos da proposta
        Route::post('/documents/proposals/{id}/cost-items', [TenantProposalCostItemController::class, 'store'])->name('tenant.documents.proposals.cost-items.store')->whereNumber('id')->middleware('permission:documents.proposal_financial.create');
        Route::patch('/documents/proposals/{id}/cost-items/{costItemId}', [TenantProposalCostItemController::class, 'update'])->name('tenant.documents.proposals.cost-items.update')->whereNumber(['id', 'costItemId'])->middleware('permission:documents.proposal_financial.edit');
        Route::delete('/documents/proposals/{id}/cost-items/{costItemId}', [TenantProposalCostItemController::class, 'destroy'])->name('tenant.documents.proposals.cost-items.destroy')->whereNumber(['id', 'costItemId'])->middleware('permission:documents.proposal_financial.delete');
        Route::post('/documents/proposals/{id}/cost-items/{costItemId}/proof', [TenantProposalCostItemController::class, 'uploadProof'])->name('tenant.documents.proposals.cost-items.proof.store')->whereNumber(['id', 'costItemId'])->middleware('permission:documents.proposals.view');
        Route::get('/documents/proposals/{id}/cost-items/{costItemId}/bill', [TenantProposalCostItemController::class, 'downloadBill'])->name('tenant.documents.proposals.cost-items.bill')->whereNumber(['id', 'costItemId'])->middleware('permission:documents.proposals.view');
        Route::get('/documents/proposals/{id}/cost-items/{costItemId}/proof', [TenantProposalCostItemController::class, 'downloadProof'])->name('tenant.documents.proposals.cost-items.proof')->whereNumber(['id', 'costItemId'])->middleware('permission:documents.proposals.view');
        Route::post('/documents/proposals/{id}/receipts', [TenantReceiptController::class, 'store'])->name('tenant.documents.proposals.receipts.store')->whereNumber('id')->middleware('permission:documents.proposal_financial.create');
        Route::delete('/documents/proposals/{id}/receipts/{receiptId}', [TenantReceiptController::class, 'destroy'])->name('tenant.documents.proposals.receipts.destroy')->whereNumber(['id', 'receiptId'])->middleware('permission:documents.proposal_financial.delete');
        Route::get('/documents/proposals/{id}/receipts/{receiptId}/pdf', [TenantProposalPdfController::class, 'receipt'])->name('tenant.documents.proposals.receipts.pdf')->whereNumber(['id', 'receiptId'])->middleware('permission:documents.proposal_financial.view');

        // Documentações - Calculadora de emolumentos
        Route::get('/documents/fee-calculator', [TenantFeeCalculatorController::class, 'index'])->name('tenant.documents.fee-calculator.index')->middleware('permission:documents.itbi_calculator.view');
        Route::get('/documents/fee-calculator/states/{state}/municipalities', TenantMunicipalityLookupController::class)->name('tenant.documents.fee-calculator.municipalities')->middleware('permission:documents.itbi_calculator.view');
        Route::get('/documents/fee-calculator/proposals/search', TenantProposalSearchController::class)->name('tenant.documents.fee-calculator.proposals.search')->middleware('permission:documents.itbi_calculator.create');
        Route::get('/documents/fee-calculator/calculate/{type}', [TenantFeeCalculatorController::class, 'create'])->name('tenant.documents.fee-calculator.create')->whereNumber('type')->middleware('permission:documents.itbi_calculator.create');
        Route::post('/documents/fee-calculator/calculate', [TenantFeeCalculatorController::class, 'store'])->name('tenant.documents.fee-calculator.store')->middleware(['permission:documents.itbi_calculator.create', 'throttle:fee-calculator']);
        Route::get('/documents/fee-calculator/results/{id}', [TenantFeeCalculationController::class, 'show'])->name('tenant.documents.fee-calculator.results.show')->whereUuid('id')->middleware('permission:documents.itbi_calculator.view');
        Route::get('/documents/fee-calculator/results/{id}/attach', [TenantProposalFeeEstimateController::class, 'create'])->name('tenant.documents.fee-calculator.attach.create')->whereUuid('id')->middleware('permission:documents.itbi_calculator.create');
        Route::post('/documents/fee-calculator/results/{id}/attach', [TenantProposalFeeEstimateController::class, 'store'])->name('tenant.documents.fee-calculator.attach.store')->whereUuid('id')->middleware('permission:documents.itbi_calculator.create');

        // Documentações - Orçamentos
        Route::get('/documents/quotes/list', [TenantQuoteController::class, 'index'])->name('tenant.documents.quotes.list')->middleware('permission:documents.quotes.view');
        Route::get('/documents/quotes/create/{feeCalculationId}', [TenantQuoteController::class, 'create'])->name('tenant.documents.quotes.create')->whereUuid('feeCalculationId')->middleware('permission:documents.quotes.create');
        Route::post('/documents/quotes/store/{feeCalculationId}', [TenantQuoteController::class, 'store'])->name('tenant.documents.quotes.store')->whereUuid('feeCalculationId')->middleware('permission:documents.quotes.create');
        Route::get('/documents/quotes/{id}', [TenantQuoteController::class, 'show'])->name('tenant.documents.quotes.show')->whereNumber('id')->middleware('permission:documents.quotes.view');
        Route::get('/documents/quotes/{id}/edit', [TenantQuoteController::class, 'edit'])->name('tenant.documents.quotes.edit')->whereNumber('id')->middleware('permission:documents.quotes.edit');
        Route::put('/documents/quotes/{id}', [TenantQuoteController::class, 'update'])->name('tenant.documents.quotes.update')->whereNumber('id')->middleware('permission:documents.quotes.edit');
        Route::delete('/documents/quotes/{id}', [TenantQuoteController::class, 'destroy'])->name('tenant.documents.quotes.destroy')->whereNumber('id')->middleware('permission:documents.quotes.delete');
        Route::get('/documents/quotes/{id}/pdf', TenantQuotePdfController::class)->name('tenant.documents.quotes.pdf')->whereNumber('id')->middleware('permission:documents.quotes.view');
        Route::post('/documents/quotes/{id}/email', TenantSendQuoteEmailController::class)->name('tenant.documents.quotes.email')->whereNumber('id')->middleware('permission:documents.quotes.edit');
        Route::post('/documents/quotes/{id}/convert', TenantConvertQuoteToProposalController::class)->name('tenant.documents.quotes.convert')->whereNumber('id')->middleware('permission:documents.quotes.edit');

        // Documentações - ITBI (municípios, alíquotas e faixas)
        Route::get('/documents/itbi/municipalities/list', [TenantItbiMunicipalityController::class, 'index'])->name('tenant.documents.itbi-municipalities.list')->middleware('permission:documents.itbi_municipalities.view');
        Route::get('/documents/itbi/municipalities/create', [TenantItbiMunicipalityController::class, 'create'])->name('tenant.documents.itbi-municipalities.create')->middleware('permission:documents.itbi_municipalities.create');
        Route::post('/documents/itbi/municipalities/store', [TenantItbiMunicipalityController::class, 'store'])->name('tenant.documents.itbi-municipalities.store')->middleware('permission:documents.itbi_municipalities.create');
        Route::get('/documents/itbi/municipalities/{id}/edit', [TenantItbiMunicipalityController::class, 'edit'])->name('tenant.documents.itbi-municipalities.edit')->whereNumber('id')->middleware('permission:documents.itbi_municipalities.edit');
        Route::put('/documents/itbi/municipalities/{id}', [TenantItbiMunicipalityController::class, 'update'])->name('tenant.documents.itbi-municipalities.update')->whereNumber('id')->middleware('permission:documents.itbi_municipalities.edit');
        Route::delete('/documents/itbi/municipalities/{id}', [TenantItbiMunicipalityController::class, 'destroy'])->name('tenant.documents.itbi-municipalities.destroy')->whereNumber('id')->middleware('permission:documents.itbi_municipalities.delete');
        Route::get('/documents/itbi/municipalities/{id}/rate', [TenantItbiRateController::class, 'edit'])->name('tenant.documents.itbi-municipalities.rates.edit')->whereNumber('id')->middleware('permission:documents.itbi_municipalities.edit');
        Route::put('/documents/itbi/municipalities/{id}/rate', [TenantItbiRateController::class, 'update'])->name('tenant.documents.itbi-municipalities.rates.update')->whereNumber('id')->middleware('permission:documents.itbi_municipalities.edit');
        Route::get('/documents/itbi/municipalities/{id}/brackets', [TenantItbiBracketController::class, 'edit'])->name('tenant.documents.itbi-municipalities.brackets.edit')->whereNumber('id')->middleware('permission:documents.itbi_municipalities.edit');
        Route::put('/documents/itbi/municipalities/{id}/brackets', [TenantItbiBracketController::class, 'update'])->name('tenant.documents.itbi-municipalities.brackets.update')->whereNumber('id')->middleware('permission:documents.itbi_municipalities.edit');

        // Documentações - Cadastros
        Route::get('/documents/banks/list', [TenantBankController::class, 'index'])->name('tenant.documents.banks.list')->middleware('permission:documents.banks.view');
        Route::get('/documents/banks/create', [TenantBankController::class, 'create'])->name('tenant.documents.banks.create')->middleware('permission:documents.banks.create');
        Route::get('/documents/banks/{id}/edit', [TenantBankController::class, 'edit'])->name('tenant.documents.banks.edit')->whereNumber('id')->middleware('permission:documents.banks.edit');
        Route::post('/documents/banks/store', [TenantBankController::class, 'store'])->name('tenant.documents.banks.store')->middleware('permission:documents.banks.create');
        Route::put('/documents/banks/{id}', [TenantBankController::class, 'update'])->name('tenant.documents.banks.update')->whereNumber('id')->middleware('permission:documents.banks.edit');
        Route::delete('/documents/banks/{id}', [TenantBankController::class, 'destroy'])->name('tenant.documents.banks.destroy')->whereNumber('id')->middleware('permission:documents.banks.delete');
        Route::patch('/documents/banks/{id}/restore', [TenantBankController::class, 'restore'])->name('tenant.documents.banks.restore')->whereNumber('id')->middleware('permission:documents.banks.delete');
        Route::get('/documents/notaries/list', [TenantNotaryController::class, 'index'])->name('tenant.documents.notaries.list')->middleware('permission:documents.notaries.view');
        Route::get('/documents/notaries/create', [TenantNotaryController::class, 'create'])->name('tenant.documents.notaries.create')->middleware('permission:documents.notaries.create');
        Route::get('/documents/notaries/{id}/edit', [TenantNotaryController::class, 'edit'])->name('tenant.documents.notaries.edit')->whereNumber('id')->middleware('permission:documents.notaries.edit');
        Route::post('/documents/notaries/store', [TenantNotaryController::class, 'store'])->name('tenant.documents.notaries.store')->middleware('permission:documents.notaries.create');
        Route::put('/documents/notaries/{id}', [TenantNotaryController::class, 'update'])->name('tenant.documents.notaries.update')->whereNumber('id')->middleware('permission:documents.notaries.edit');
        Route::delete('/documents/notaries/{id}', [TenantNotaryController::class, 'destroy'])->name('tenant.documents.notaries.destroy')->whereNumber('id')->middleware('permission:documents.notaries.delete');
        Route::patch('/documents/notaries/{id}/restore', [TenantNotaryController::class, 'restore'])->name('tenant.documents.notaries.restore')->whereNumber('id')->middleware('permission:documents.notaries.delete');
        Route::get('/documents/contract-types/list', [TenantContractTypeController::class, 'index'])->name('tenant.documents.contract-types.list')->middleware('permission:documents.contract_types.view');
        Route::get('/documents/contract-types/create', [TenantContractTypeController::class, 'create'])->name('tenant.documents.contract-types.create')->middleware('permission:documents.contract_types.create');
        Route::get('/documents/contract-types/{id}/edit', [TenantContractTypeController::class, 'edit'])->name('tenant.documents.contract-types.edit')->whereNumber('id')->middleware('permission:documents.contract_types.edit');
        Route::post('/documents/contract-types/store', [TenantContractTypeController::class, 'store'])->name('tenant.documents.contract-types.store')->middleware('permission:documents.contract_types.create');
        Route::put('/documents/contract-types/{id}', [TenantContractTypeController::class, 'update'])->name('tenant.documents.contract-types.update')->whereNumber('id')->middleware('permission:documents.contract_types.edit');
        Route::delete('/documents/contract-types/{id}', [TenantContractTypeController::class, 'destroy'])->name('tenant.documents.contract-types.destroy')->whereNumber('id')->middleware('permission:documents.contract_types.delete');
        Route::patch('/documents/contract-types/{id}/restore', [TenantContractTypeController::class, 'restore'])->name('tenant.documents.contract-types.restore')->whereNumber('id')->middleware('permission:documents.contract_types.delete');
        Route::get('/documents/cost-types/list', [TenantCostTypeController::class, 'index'])->name('tenant.documents.cost-types.list')->middleware('permission:documents.cost_types.view');
        Route::get('/documents/cost-types/create', [TenantCostTypeController::class, 'create'])->name('tenant.documents.cost-types.create')->middleware('permission:documents.cost_types.create');
        Route::get('/documents/cost-types/{id}/edit', [TenantCostTypeController::class, 'edit'])->name('tenant.documents.cost-types.edit')->whereNumber('id')->middleware('permission:documents.cost_types.edit');
        Route::post('/documents/cost-types/store', [TenantCostTypeController::class, 'store'])->name('tenant.documents.cost-types.store')->middleware('permission:documents.cost_types.create');
        Route::put('/documents/cost-types/{id}', [TenantCostTypeController::class, 'update'])->name('tenant.documents.cost-types.update')->whereNumber('id')->middleware('permission:documents.cost_types.edit');
        Route::delete('/documents/cost-types/{id}', [TenantCostTypeController::class, 'destroy'])->name('tenant.documents.cost-types.destroy')->whereNumber('id')->middleware('permission:documents.cost_types.delete');
        Route::patch('/documents/cost-types/{id}/restore', [TenantCostTypeController::class, 'restore'])->name('tenant.documents.cost-types.restore')->whereNumber('id')->middleware('permission:documents.cost_types.delete');
        Route::get('/documents/property-types/list', [TenantPropertyTypeController::class, 'index'])->name('tenant.documents.property-types.list')->middleware('permission:documents.property_types.view');
        Route::get('/documents/property-types/create', [TenantPropertyTypeController::class, 'create'])->name('tenant.documents.property-types.create')->middleware('permission:documents.property_types.create');
        Route::get('/documents/property-types/{id}/edit', [TenantPropertyTypeController::class, 'edit'])->name('tenant.documents.property-types.edit')->whereNumber('id')->middleware('permission:documents.property_types.edit');
        Route::post('/documents/property-types/store', [TenantPropertyTypeController::class, 'store'])->name('tenant.documents.property-types.store')->middleware('permission:documents.property_types.create');
        Route::put('/documents/property-types/{id}', [TenantPropertyTypeController::class, 'update'])->name('tenant.documents.property-types.update')->whereNumber('id')->middleware('permission:documents.property_types.edit');
        Route::delete('/documents/property-types/{id}', [TenantPropertyTypeController::class, 'destroy'])->name('tenant.documents.property-types.destroy')->whereNumber('id')->middleware('permission:documents.property_types.delete');
        Route::patch('/documents/property-types/{id}/restore', [TenantPropertyTypeController::class, 'restore'])->name('tenant.documents.property-types.restore')->whereNumber('id')->middleware('permission:documents.property_types.delete');
        Route::get('/documents/developments/list', [TenantDevelopmentController::class, 'index'])->name('tenant.documents.developments.list')->middleware('permission:documents.developments.view');
        Route::get('/documents/developments/create', [TenantDevelopmentController::class, 'create'])->name('tenant.documents.developments.create')->middleware('permission:documents.developments.create');
        Route::get('/documents/developments/{id}/edit', [TenantDevelopmentController::class, 'edit'])->name('tenant.documents.developments.edit')->whereNumber('id')->middleware('permission:documents.developments.edit');
        Route::post('/documents/developments/store', [TenantDevelopmentController::class, 'store'])->name('tenant.documents.developments.store')->middleware('permission:documents.developments.create');
        Route::put('/documents/developments/{id}', [TenantDevelopmentController::class, 'update'])->name('tenant.documents.developments.update')->whereNumber('id')->middleware('permission:documents.developments.edit');
        Route::delete('/documents/developments/{id}', [TenantDevelopmentController::class, 'destroy'])->name('tenant.documents.developments.destroy')->whereNumber('id')->middleware('permission:documents.developments.delete');
        Route::patch('/documents/developments/{id}/restore', [TenantDevelopmentController::class, 'restore'])->name('tenant.documents.developments.restore')->whereNumber('id')->middleware('permission:documents.developments.delete');
        Route::get('/documents/stages/list', [TenantStageController::class, 'index'])->name('tenant.documents.stages.list')->middleware('permission:documents.stages.view');
        Route::get('/documents/stages/create', [TenantStageController::class, 'create'])->name('tenant.documents.stages.create')->middleware('permission:documents.stages.create');
        Route::get('/documents/stages/{id}/edit', [TenantStageController::class, 'edit'])->name('tenant.documents.stages.edit')->whereNumber('id')->middleware('permission:documents.stages.edit');
        Route::post('/documents/stages/store', [TenantStageController::class, 'store'])->name('tenant.documents.stages.store')->middleware('permission:documents.stages.create');
        Route::put('/documents/stages/{id}', [TenantStageController::class, 'update'])->name('tenant.documents.stages.update')->whereNumber('id')->middleware('permission:documents.stages.edit');
        Route::delete('/documents/stages/{id}', [TenantStageController::class, 'destroy'])->name('tenant.documents.stages.destroy')->whereNumber('id')->middleware('permission:documents.stages.delete');
        Route::patch('/documents/stages/{id}/restore', [TenantStageController::class, 'restore'])->name('tenant.documents.stages.restore')->whereNumber('id')->middleware('permission:documents.stages.delete');
        Route::patch('/documents/stages/{id}/move', [TenantStageController::class, 'move'])->name('tenant.documents.stages.move')->whereNumber('id')->middleware('permission:documents.stages.edit');
        Route::get('/documents/billable-services/list', [TenantBillableServiceController::class, 'index'])->name('tenant.documents.billable-services.list')->middleware('permission:documents.billable_services.view');
        Route::get('/documents/billable-services/create', [TenantBillableServiceController::class, 'create'])->name('tenant.documents.billable-services.create')->middleware('permission:documents.billable_services.create');
        Route::get('/documents/billable-services/{id}/edit', [TenantBillableServiceController::class, 'edit'])->name('tenant.documents.billable-services.edit')->whereNumber('id')->middleware('permission:documents.billable_services.edit');
        Route::post('/documents/billable-services/store', [TenantBillableServiceController::class, 'store'])->name('tenant.documents.billable-services.store')->middleware('permission:documents.billable_services.create');
        Route::put('/documents/billable-services/{id}', [TenantBillableServiceController::class, 'update'])->name('tenant.documents.billable-services.update')->whereNumber('id')->middleware('permission:documents.billable_services.edit');
        Route::delete('/documents/billable-services/{id}', [TenantBillableServiceController::class, 'destroy'])->name('tenant.documents.billable-services.destroy')->whereNumber('id')->middleware('permission:documents.billable_services.delete');
        Route::patch('/documents/billable-services/{id}/restore', [TenantBillableServiceController::class, 'restore'])->name('tenant.documents.billable-services.restore')->whereNumber('id')->middleware('permission:documents.billable_services.delete');

    });
});
