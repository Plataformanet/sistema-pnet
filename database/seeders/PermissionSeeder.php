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
                    'services.categories.delete',
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
                    'products.categories.delete',
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
                'subcategories' => [
                    'name' => [
                        'finance.subcategories.view',
                        'finance.subcategories.edit',
                        'finance.subcategories.create',
                        'finance.subcategories.delete',
                    ],
                    'display_name' => [
                        'Subcategorias (Visualizar)',
                        'Subcategorias (Editar)',
                        'Subcategorias (Criar)',
                        'Subcategorias (Excluir)',
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
                'spending_flow' => [
                    'name' => [
                        'finance.spending_flow.view',
                    ],
                    'display_name' => [
                        'Fluxo de Gastos (Visualizar)',
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
                'banks' => [
                    'name' => [
                        'documents.banks.view',
                        'documents.banks.edit',
                        'documents.banks.create',
                        'documents.banks.delete',
                    ],
                    'display_name' => [
                        'Bancos (Visualizar)',
                        'Bancos (Editar)',
                        'Bancos (Criar)',
                        'Bancos (Excluir)',
                    ],
                ],
                'notaries' => [
                    'name' => [
                        'documents.notaries.view',
                        'documents.notaries.edit',
                        'documents.notaries.create',
                        'documents.notaries.delete',
                    ],
                    'display_name' => [
                        'Cartórios (Visualizar)',
                        'Cartórios (Editar)',
                        'Cartórios (Criar)',
                        'Cartórios (Excluir)',
                    ],
                ],
                'contract_types' => [
                    'name' => [
                        'documents.contract_types.view',
                        'documents.contract_types.edit',
                        'documents.contract_types.create',
                        'documents.contract_types.delete',
                    ],
                    'display_name' => [
                        'Tipos de Contrato (Visualizar)',
                        'Tipos de Contrato (Editar)',
                        'Tipos de Contrato (Criar)',
                        'Tipos de Contrato (Excluir)',
                    ],
                ],
                'cost_types' => [
                    'name' => [
                        'documents.cost_types.view',
                        'documents.cost_types.edit',
                        'documents.cost_types.create',
                        'documents.cost_types.delete',
                    ],
                    'display_name' => [
                        'Tipos de Custo (Visualizar)',
                        'Tipos de Custo (Editar)',
                        'Tipos de Custo (Criar)',
                        'Tipos de Custo (Excluir)',
                    ],
                ],
                'property_types' => [
                    'name' => [
                        'documents.property_types.view',
                        'documents.property_types.edit',
                        'documents.property_types.create',
                        'documents.property_types.delete',
                    ],
                    'display_name' => [
                        'Tipos de Imóvel (Visualizar)',
                        'Tipos de Imóvel (Editar)',
                        'Tipos de Imóvel (Criar)',
                        'Tipos de Imóvel (Excluir)',
                    ],
                ],
                'developments' => [
                    'name' => [
                        'documents.developments.view',
                        'documents.developments.edit',
                        'documents.developments.create',
                        'documents.developments.delete',
                    ],
                    'display_name' => [
                        'Empreendimentos (Visualizar)',
                        'Empreendimentos (Editar)',
                        'Empreendimentos (Criar)',
                        'Empreendimentos (Excluir)',
                    ],
                ],
                'stages' => [
                    'name' => [
                        'documents.stages.view',
                        'documents.stages.edit',
                        'documents.stages.create',
                        'documents.stages.delete',
                    ],
                    'display_name' => [
                        'Etapas da Timeline (Visualizar)',
                        'Etapas da Timeline (Editar)',
                        'Etapas da Timeline (Criar)',
                        'Etapas da Timeline (Excluir)',
                    ],
                ],
                'billable_services' => [
                    'name' => [
                        'documents.billable_services.view',
                        'documents.billable_services.edit',
                        'documents.billable_services.create',
                        'documents.billable_services.delete',
                    ],
                    'display_name' => [
                        'Serviços Cobráveis (Visualizar)',
                        'Serviços Cobráveis (Editar)',
                        'Serviços Cobráveis (Criar)',
                        'Serviços Cobráveis (Excluir)',
                    ],
                ],
                'proposal_documents' => [
                    'name' => [
                        'documents.proposal_documents.view',
                        'documents.proposal_documents.edit',
                        'documents.proposal_documents.create',
                        'documents.proposal_documents.delete',
                    ],
                    'display_name' => [
                        'Documentos da Proposta (Visualizar)',
                        'Documentos da Proposta (Editar)',
                        'Documentos da Proposta (Criar)',
                        'Documentos da Proposta (Excluir)',
                    ],
                ],
                'proposal_timeline' => [
                    'name' => [
                        'documents.proposal_timeline.view',
                        'documents.proposal_timeline.edit',
                        'documents.proposal_timeline.create',
                        'documents.proposal_timeline.delete',
                    ],
                    'display_name' => [
                        'Acompanhamento da Proposta (Visualizar)',
                        'Acompanhamento da Proposta (Editar)',
                        'Acompanhamento da Proposta (Criar)',
                        'Acompanhamento da Proposta (Excluir)',
                    ],
                ],
                'proposal_financial' => [
                    'name' => [
                        'documents.proposal_financial.view',
                        'documents.proposal_financial.edit',
                        'documents.proposal_financial.create',
                        'documents.proposal_financial.delete',
                    ],
                    'display_name' => [
                        'Pagamentos e Recibos da Proposta (Visualizar)',
                        'Pagamentos e Recibos da Proposta (Editar)',
                        'Pagamentos e Recibos da Proposta (Criar)',
                        'Pagamentos e Recibos da Proposta (Excluir)',
                    ],
                ],
                'quotes' => [
                    'name' => [
                        'documents.quotes.view',
                        'documents.quotes.edit',
                        'documents.quotes.create',
                        'documents.quotes.delete',
                    ],
                    'display_name' => [
                        'Orçamentos (Visualizar)',
                        'Orçamentos (Editar)',
                        'Orçamentos (Criar)',
                        'Orçamentos (Excluir)',
                    ],
                ],
                'itbi_municipalities' => [
                    'name' => [
                        'documents.itbi_municipalities.view',
                        'documents.itbi_municipalities.edit',
                        'documents.itbi_municipalities.create',
                        'documents.itbi_municipalities.delete',
                    ],
                    'display_name' => [
                        'ITBI - Municípios (Visualizar)',
                        'ITBI - Municípios (Editar)',
                        'ITBI - Municípios (Criar)',
                        'ITBI - Municípios (Excluir)',
                    ],
                ],
            ],

            'settings' => [
                'company' => [
                    'name' => [
                        'settings.company.view',
                        'settings.company.edit',
                    ],
                    'display_name' => [
                        'Empresa (Visualizar)',
                        'Empresa (Editar)',
                    ],
                ],
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
            'drive' => [
                'drives' => [
                    'name' => [
                        'drive.drives.view',
                        'drive.drives.edit',
                        'drive.drives.create',
                        'drive.drives.delete',
                    ],
                    'display_name' => [
                        'Drives (Visualizar)',
                        'Drives (Editar)',
                        'Drives (Criar)',
                        'Drives (Excluir)',
                    ],
                ],
                'folders' => [
                    'name' => [
                        'drive.folders.view',
                        'drive.folders.edit',
                        'drive.folders.create',
                        'drive.folders.delete',
                    ],
                    'display_name' => [
                        'Pastas (Visualizar)',
                        'Pastas (Editar)',
                        'Pastas (Criar)',
                        'Pastas (Excluir)',
                    ],
                ],
                'trash' => [
                    'name' => [
                        'drive.trash.view',
                        'drive.trash.edit',
                        'drive.trash.create',
                        'drive.trash.delete',
                    ],
                    'display_name' => [
                        'Lixeira (Visualizar)',
                        'Lixeira (Editar)',
                        'Lixeira (Criar)',
                        'Lixeira (Excluir)',
                    ],
                ],
                'logs' => [
                    'name' => [
                        'drive.logs.view',
                        'drive.logs.edit',
                        'drive.logs.create',
                        'drive.logs.delete',
                    ],
                    'display_name' => [
                        'Logs (Visualizar)',
                        'Logs (Editar)',
                        'Logs (Criar)',
                        'Logs (Excluir)',
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

            foreach ($permissions['drive'] as $permission) {
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
