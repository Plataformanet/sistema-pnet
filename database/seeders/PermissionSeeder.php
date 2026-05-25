<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
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

                'clients' => [
                    'name' => [
                        'registrations.clients.view',
                        'registrations.clients.edit',
                        'registrations.clients.create',
                        'registrations.clients.delete',
                    ],

                    'display_name' => [
                        'Clientes (Visualizar)',
                        'Clientes (Editar)',
                        'Clientes (Criar)',
                        'Clientes (Excluir)',
                    ],
                ],

                'suppliers' => [
                    'name' => [
                        'registrations.suppliers.view',
                        'registrations.suppliers.edit',
                        'registrations.suppliers.create',
                        'registrations.suppliers.delete',
                    ],
                    'display_name' => [
                        'Fornecedores (Visualizar)',
                        'Fornecedores (Editar)',
                        'Fornecedores (Criar)',
                        'Fornecedores (Excluir)',
                    ],
                ],

                'employees' => [
                    'name' => [
                        'registrations.employees.view',
                        'registrations.employees.edit',
                        'registrations.employees.create',
                        'registrations.employees.delete',
                    ],
                    'display_name' => [
                        'Funcionários (Visualizar)',
                        'Funcionários (Editar)',
                        'Funcionários (Criar)',
                        'Funcionários (Excluir)',
                    ],
                ],
            ],

            'sales' => [
                'name' => [
                    'sales.sales.view',
                    'sales.sales.edit',
                    'sales.sales.create',
                    'sales.sales.delete',
                    'sales.quotations.view',
                    'sales.quotations.edit',
                    'sales.quotations.create',
                    'sales.quotations.delete',
                ],
                'display_name' => [
                    'Vendas (Visualizar)',
                    'Vendas (Editar)',
                    'Vendas (Criar)',
                    'Vendas (Excluir)',
                    'Orçamentos (Visualizar)',
                    'Orçamentos (Editar)',
                    'Orçamentos (Criar)',
                    'Orçamentos (Excluir)',
                ],
            ],

            'services' => [
                'name' => [
                    'services.services.view',
                    'services.services.edit',
                    'services.services.create',
                    'services.services.delete',
                    'services.categories.view',
                    'services.categories.edit',
                    'services.categories.create',
                    'services.categories.delete'
                ],

                'display_name' => [
                    'Serviços (Visualizar)',
                    'Serviços (Editar)',
                    'Serviços (Criar)',
                    'Serviços (Excluir)',
                    'Categoria de Serviços (Visualizar)',
                    'Categoria de Serviços (Editar)',
                    'Categoria de Serviços (Criar)',
                    'Categoria de Serviços (Excluir)',
                ],
            ],

            'products' => [
                'name' => [
                    'products.products.view',
                    'products.products.edit',
                    'products.products.create',
                    'products.products.delete',
                    'products.categories.view',
                    'products.categories.edit',
                    'products.categories.create',
                    'products.categories.delete'
                ],
                'display_name' => [
                    'Produtos (Visualizar)',
                    'Produtos (Editar)',
                    'Produtos (Criar)',
                    'Produtos (Excluir)',
                    'Categoria de Produtos (Visualizar)',
                    'Categoria de Produtos (Editar)',
                    'Categoria de Produtos (Criar)',
                    'Categoria de Produtos (Excluir)',
                ],
            ],

            'finance' => [
                'categories' => [
                    'name' => [
                        'finance.categories.view',
                        'finance.categories.edit',
                        'finance.categories.create',
                        'finance.categories.delete',
                    ],
                    'display_name' => [
                        'Categorias (Visualizar)',
                        'Categorias (Editar)',
                        'Categorias (Criar)',
                        'Categorias (Excluir)',
                    ],
                ],
                'accounts' => [
                    'name' => [
                        'finance.accounts.view',
                        'finance.accounts.edit',
                        'finance.accounts.create',
                        'finance.accounts.delete',
                    ],
                    'display_name' => [
                        'Contas (Visualizar)',
                        'Contas (Editar)',
                        'Contas (Criar)',
                        'Contas (Excluir)',
                    ],
                ],
                'accounts_payable' => [
                    'name' => [
                        'finance.accounts_payable.view',
                        'finance.accounts_payable.edit',
                        'finance.accounts_payable.create',
                        'finance.accounts_payable.delete',
                    ],
                    'display_name' => [
                        'Contas a Pagar (Visualizar)',
                        'Contas a Pagar (Editar)',
                        'Contas a Pagar (Criar)',
                        'Contas a Pagar (Excluir)',
                    ],
                ],
                'accounts_receivable' => [
                    'name' => [
                        'finance.accounts_receivable.view',
                        'finance.accounts_receivable.edit',
                        'finance.accounts_receivable.create',
                        'finance.accounts_receivable.delete',
                    ],
                    'display_name' => [
                        'Contas a Receber (Visualizar)',
                        'Contas a Receber (Editar)',
                        'Contas a Receber (Criar)',
                        'Contas a Receber (Excluir)',
                    ],
                ],
                'cash_flow' => [
                    'name' => [
                        'finance.cash_flow.view',
                    ],
                    'display_name' => [
                        'Fluxo de Caixa (Visualizar)',
                    ],
                ],
                'expenses_flow' => [
                    'name' => [
                        'finance.expenses_flow.view',
                    ],
                    'display_name' => [
                        'Fluxo de Despesas (Visualizar)',
                    ],
                ],
                'billing' => [
                    'name' => [
                        'finance.billing.view',
                    ],
                    'display_name' => [
                        'Faturamento (Visualizar)',
                    ],
                ],
            ],

            'documents' => [
                'proposals' => [
                    'name' => [
                        'documents.proposals.view',
                        'documents.proposals.edit',
                        'documents.proposals.create',
                        'documents.proposals.delete',
                    ],
                    'display_name' => [
                        'Propostas (Visualizar)',
                        'Propostas (Editar)',
                        'Propostas (Criar)',
                        'Propostas (Excluir)',
                    ],
                ],
                'itbi_calculator' => [
                    'name' => [
                        'documents.itbi_calculator.view',
                        'documents.itbi_calculator.edit',
                        'documents.itbi_calculator.create',
                        'documents.itbi_calculator.delete',
                    ],
                    'display_name' => [
                        'Calculadora de ITBI (Visualizar)',
                        'Calculadora de ITBI (Editar)',
                        'Calculadora de ITBI (Criar)',
                        'Calculadora de ITBI (Excluir)',
                    ],
                ],
                'contracts' => [
                    'name' => [
                        'documents.contracts.view',
                        'documents.contracts.edit',
                        'documents.contracts.create',
                        'documents.contracts.delete',
                    ],
                    'display_name' => [
                        'Contratos (Visualizar)',
                        'Contratos (Editar)',
                        'Contratos (Criar)',
                        'Contratos (Excluir)',
                    ],
                ],
            ],

            'settings' => [
                'roles' => [
                    'name' => [
                        'settings.roles.view',
                        'settings.roles.edit',
                        'settings.roles.create',
                        'settings.roles.delete',
                    ],
                    'display_name' => [
                        'Cargos (Visualizar)',
                        'Cargos (Editar)',
                        'Cargos (Criar)',
                        'Cargos (Excluir)',
                    ],
                ],
                'users' => [
                    'name' => [
                        'settings.users.view',
                        'settings.users.edit',
                        'settings.users.create',
                        'settings.users.delete',
                    ],
                    'display_name' => [
                        'Usuários (Visualizar)',
                        'Usuários (Editar)',
                        'Usuários (Criar)',
                        'Usuários (Excluir)',
                    ],
                ],
            ],
        ];

        $modules = Module::where('is_core', 1)->get();

        foreach ($modules as $module) {
            foreach ($permissions['registrations'] as $permission) {
                foreach ($permission['name'] as $key => $name) {
                    $slug = explode('.', $name);
                    if ($slug[0] == $module->slug) {
                        Permission::insert([
                            'name' => $name,
                            'display_name' => $permission['display_name'][$key],
                            'module_id' => $module->id,
                        ]);
                    }
                }
            }

            foreach ($permissions['sales']['name'] as $key => $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name' => $permission,
                        'display_name' => $permissions['sales']['display_name'][$key],
                        'module_id' => $module->id,
                    ]);
                }
            }

            foreach ($permissions['services']['name'] as $key => $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name' => $permission,
                        'display_name' => $permissions['services']['display_name'][$key],
                        'module_id' => $module->id,
                    ]);
                }
            }

            foreach ($permissions['products']['name'] as $key => $permission) {
                $slug = explode('.', $permission);
                if ($slug[0] == $module->slug) {
                    Permission::insert([
                        'name' => $permission,
                        'display_name' => $permissions['products']['display_name'][$key],
                        'module_id' => $module->id,
                    ]);
                }
            }

            foreach ($permissions['finance'] as $permission) {
                foreach ($permission['name'] as $key => $name) {
                    $slug = explode('.', $name);
                    if ($slug[0] == $module->slug) {
                        Permission::insert([
                            'name' => $name,
                            'display_name' => $permission['display_name'][$key],
                            'module_id' => $module->id,
                        ]);
                    }
                }
            }

            foreach ($permissions['documents'] as $permission) {
                foreach ($permission['name'] as $key => $name) {
                    $slug = explode('.', $name);
                    if ($slug[0] == $module->slug) {
                        Permission::insert([
                            'name' => $name,
                            'display_name' => $permission['display_name'][$key],
                            'module_id' => $module->id,
                        ]);
                    }
                }
            }

            foreach ($permissions['settings'] as $permission) {
                foreach ($permission['name'] as $key => $name) {
                    $slug = explode('.', $name);
                    if ($slug[0] == $module->slug) {
                        Permission::insert([
                            'name' => $name,
                            'display_name' => $permission['display_name'][$key],
                            'module_id' => $module->id,
                        ]);
                    }
                }
            }
        }
    }
}
