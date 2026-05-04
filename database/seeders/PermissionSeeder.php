<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $permissions = [

            'registrations' => [
                'registrations.clients.view',
                'registrations.clients.update',
                'registrations.clients.create',
                'registrations.clients.delete',
                'registrations.clients.show',

                'registrations.suppliers.view',
                'registrations.suppliers.update',
                'registrations.suppliers.create',
                'registrations.suppliers.delete',
                'registrations.suppliers.show',

                'registrations.employees.view',
                'registrations.employees.update',
                'registrations.employees.create',
                'registrations.employees.delete',
                'registrations.employees.show',

                'registrations.users.view',
                'registrations.users.update',
                'registrations.users.create',
                'registrations.users.delete',
                'registrations.users.show',
            ],

            'sales'         => [
                'sales.sales.view',
                'sales.sales.edit',
                'sales.sales.create',
                'sales.sales.delete',
                'sales.sales.show',

                'sales.quotations.view',
                'sales.quotations.edit',
                'sales.quotations.create',
                'sales.quotations.delete',
                'sales.quotations.show',
            ],

            'services'      => [
                'services.services.view',
                'services.services.edit',
                'services.services.create',
                'services.services.delete',
                'services.services.show',

                'services.categories.view',
                'services.categories.edit',
                'services.categories.create',
                'services.categories.delete',
                'services.categories.show',
            ],

            'products'      => [
                'products.products.view',
                'products.products.edit',
                'products.products.create',
                'products.products.delete',
                'products.products.show',

                'products.categories.view',
                'products.categories.edit',
                'products.categories.create',
                'products.categories.delete',
                'products.categories.show',
            ],

            'finance'       => [
                'finance.categories.view',
                'finance.categories.edit',
                'finance.categories.create',
                'finance.categories.delete',
                'finance.categories.show',

                'finance.accounts.view',
                'finance.accounts.edit',
                'finance.accounts.create',
                'finance.accounts.delete',
                'finance.accounts.show',

                'finance.accounts_payable.view',
                'finance.accounts_payable.edit',
                'finance.accounts_payable.create',
                'finance.accounts_payable.delete',
                'finance.accounts_payable.show',

                'finance.accounts_receivable.view',
                'finance.accounts_receivable.edit',
                'finance.accounts_receivable.create',
                'finance.accounts_receivable.delete',
                'finance.accounts_receivable.show',

                'finance.cash_flow.view',

                'finance.expenses_flow.view',

                'finance.billing.view',
            ],

            'documents'     => [
                'documents.proposals.view',
                'documents.proposals.edit',
                'documents.proposals.create',
                'documents.proposals.delete',
                'documents.proposals.show',

                'documents.itbi_calculator.view',
                'documents.itbi_calculator.edit',
                'documents.itbi_calculator.create',
                'documents.itbi_calculator.delete',
                'documents.itbi_calculator.show',

                'documents.contracts.view',
                'documents.contracts.edit',
                'documents.contracts.create',
                'documents.contracts.delete',
                'documents.contracts.show',
            ],
        ];

        $modules = Module::where('is_core', 1)->get();

        foreach ($modules as $module) {
            foreach ($permissions['registrations'] as $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name'      => $permission,
                        'module_id' => $module->id,
                    ]);
                }
            }

            foreach ($permissions['sales'] as $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name'      => $permission,
                        'module_id' => $module->id,
                    ]);
                }
            }

            foreach ($permissions['services'] as $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name'      => $permission,
                        'module_id' => $module->id,
                    ]);
                }
            }

            foreach ($permissions['products'] as $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name'      => $permission,
                        'module_id' => $module->id,
                    ]);
                }
            }

            foreach ($permissions['finance'] as $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name'      => $permission,
                        'module_id' => $module->id,
                    ]);
                }
            }

            foreach ($permissions['documents'] as $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name'      => $permission,
                        'module_id' => $module->id,
                    ]);
                }
            }
        }
    }
}
