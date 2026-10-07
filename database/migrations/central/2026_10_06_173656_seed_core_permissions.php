<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registra no catálogo central as permissões de todos os módulos core.
 * Substitui o antigo PermissionSeeder. O módulo de cada permissão é o prefixo
 * do nome (`<modulo>.<recurso>.<acao>`). Idempotente: em ambientes onde o
 * seeder já rodou, só insere as permissões que ainda faltam.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{name: string, display_name: string}>
     */
    private array $permissions = [
        ['name' => 'registrations.clients.view', 'display_name' => 'Clientes (Visualizar)'],
        ['name' => 'registrations.clients.edit', 'display_name' => 'Clientes (Editar)'],
        ['name' => 'registrations.clients.create', 'display_name' => 'Clientes (Criar)'],
        ['name' => 'registrations.clients.delete', 'display_name' => 'Clientes (Excluir)'],
        ['name' => 'registrations.suppliers.view', 'display_name' => 'Fornecedores (Visualizar)'],
        ['name' => 'registrations.suppliers.edit', 'display_name' => 'Fornecedores (Editar)'],
        ['name' => 'registrations.suppliers.create', 'display_name' => 'Fornecedores (Criar)'],
        ['name' => 'registrations.suppliers.delete', 'display_name' => 'Fornecedores (Excluir)'],
        ['name' => 'registrations.employees.view', 'display_name' => 'Funcionários (Visualizar)'],
        ['name' => 'registrations.employees.edit', 'display_name' => 'Funcionários (Editar)'],
        ['name' => 'registrations.employees.create', 'display_name' => 'Funcionários (Criar)'],
        ['name' => 'registrations.employees.delete', 'display_name' => 'Funcionários (Excluir)'],

        ['name' => 'sales.sales.view', 'display_name' => 'Vendas (Visualizar)'],
        ['name' => 'sales.sales.edit', 'display_name' => 'Vendas (Editar)'],
        ['name' => 'sales.sales.create', 'display_name' => 'Vendas (Criar)'],
        ['name' => 'sales.sales.delete', 'display_name' => 'Vendas (Excluir)'],
        ['name' => 'sales.quotations.view', 'display_name' => 'Orçamentos (Visualizar)'],
        ['name' => 'sales.quotations.edit', 'display_name' => 'Orçamentos (Editar)'],
        ['name' => 'sales.quotations.create', 'display_name' => 'Orçamentos (Criar)'],
        ['name' => 'sales.quotations.delete', 'display_name' => 'Orçamentos (Excluir)'],

        ['name' => 'services.services.view', 'display_name' => 'Serviços (Visualizar)'],
        ['name' => 'services.services.edit', 'display_name' => 'Serviços (Editar)'],
        ['name' => 'services.services.create', 'display_name' => 'Serviços (Criar)'],
        ['name' => 'services.services.delete', 'display_name' => 'Serviços (Excluir)'],
        ['name' => 'services.categories.view', 'display_name' => 'Categoria de Serviços (Visualizar)'],
        ['name' => 'services.categories.edit', 'display_name' => 'Categoria de Serviços (Editar)'],
        ['name' => 'services.categories.create', 'display_name' => 'Categoria de Serviços (Criar)'],
        ['name' => 'services.categories.delete', 'display_name' => 'Categoria de Serviços (Excluir)'],

        ['name' => 'products.products.view', 'display_name' => 'Produtos (Visualizar)'],
        ['name' => 'products.products.edit', 'display_name' => 'Produtos (Editar)'],
        ['name' => 'products.products.create', 'display_name' => 'Produtos (Criar)'],
        ['name' => 'products.products.delete', 'display_name' => 'Produtos (Excluir)'],
        ['name' => 'products.categories.view', 'display_name' => 'Categoria de Produtos (Visualizar)'],
        ['name' => 'products.categories.edit', 'display_name' => 'Categoria de Produtos (Editar)'],
        ['name' => 'products.categories.create', 'display_name' => 'Categoria de Produtos (Criar)'],
        ['name' => 'products.categories.delete', 'display_name' => 'Categoria de Produtos (Excluir)'],

        ['name' => 'finance.categories.view', 'display_name' => 'Categorias (Visualizar)'],
        ['name' => 'finance.categories.edit', 'display_name' => 'Categorias (Editar)'],
        ['name' => 'finance.categories.create', 'display_name' => 'Categorias (Criar)'],
        ['name' => 'finance.categories.delete', 'display_name' => 'Categorias (Excluir)'],
        ['name' => 'finance.subcategories.view', 'display_name' => 'Subcategorias (Visualizar)'],
        ['name' => 'finance.subcategories.edit', 'display_name' => 'Subcategorias (Editar)'],
        ['name' => 'finance.subcategories.create', 'display_name' => 'Subcategorias (Criar)'],
        ['name' => 'finance.subcategories.delete', 'display_name' => 'Subcategorias (Excluir)'],
        ['name' => 'finance.accounts.view', 'display_name' => 'Contas (Visualizar)'],
        ['name' => 'finance.accounts.edit', 'display_name' => 'Contas (Editar)'],
        ['name' => 'finance.accounts.create', 'display_name' => 'Contas (Criar)'],
        ['name' => 'finance.accounts.delete', 'display_name' => 'Contas (Excluir)'],
        ['name' => 'finance.accounts_payable.view', 'display_name' => 'Contas a Pagar (Visualizar)'],
        ['name' => 'finance.accounts_payable.edit', 'display_name' => 'Contas a Pagar (Editar)'],
        ['name' => 'finance.accounts_payable.create', 'display_name' => 'Contas a Pagar (Criar)'],
        ['name' => 'finance.accounts_payable.delete', 'display_name' => 'Contas a Pagar (Excluir)'],
        ['name' => 'finance.accounts_receivable.view', 'display_name' => 'Contas a Receber (Visualizar)'],
        ['name' => 'finance.accounts_receivable.edit', 'display_name' => 'Contas a Receber (Editar)'],
        ['name' => 'finance.accounts_receivable.create', 'display_name' => 'Contas a Receber (Criar)'],
        ['name' => 'finance.accounts_receivable.delete', 'display_name' => 'Contas a Receber (Excluir)'],
        ['name' => 'finance.cash_flow.view', 'display_name' => 'Fluxo de Caixa (Visualizar)'],
        ['name' => 'finance.spending_flow.view', 'display_name' => 'Fluxo de Gastos (Visualizar)'],
        ['name' => 'finance.billing.view', 'display_name' => 'Faturamento (Visualizar)'],

        ['name' => 'documents.proposals.view', 'display_name' => 'Propostas (Visualizar)'],
        ['name' => 'documents.proposals.edit', 'display_name' => 'Propostas (Editar)'],
        ['name' => 'documents.proposals.create', 'display_name' => 'Propostas (Criar)'],
        ['name' => 'documents.proposals.delete', 'display_name' => 'Propostas (Excluir)'],
        ['name' => 'documents.itbi_calculator.view', 'display_name' => 'Calculadora de ITBI (Visualizar)'],
        ['name' => 'documents.itbi_calculator.edit', 'display_name' => 'Calculadora de ITBI (Editar)'],
        ['name' => 'documents.itbi_calculator.create', 'display_name' => 'Calculadora de ITBI (Criar)'],
        ['name' => 'documents.itbi_calculator.delete', 'display_name' => 'Calculadora de ITBI (Excluir)'],
        ['name' => 'documents.contracts.view', 'display_name' => 'Contratos (Visualizar)'],
        ['name' => 'documents.contracts.edit', 'display_name' => 'Contratos (Editar)'],
        ['name' => 'documents.contracts.create', 'display_name' => 'Contratos (Criar)'],
        ['name' => 'documents.contracts.delete', 'display_name' => 'Contratos (Excluir)'],
        ['name' => 'documents.banks.view', 'display_name' => 'Bancos (Visualizar)'],
        ['name' => 'documents.banks.edit', 'display_name' => 'Bancos (Editar)'],
        ['name' => 'documents.banks.create', 'display_name' => 'Bancos (Criar)'],
        ['name' => 'documents.banks.delete', 'display_name' => 'Bancos (Excluir)'],
        ['name' => 'documents.notaries.view', 'display_name' => 'Cartórios (Visualizar)'],
        ['name' => 'documents.notaries.edit', 'display_name' => 'Cartórios (Editar)'],
        ['name' => 'documents.notaries.create', 'display_name' => 'Cartórios (Criar)'],
        ['name' => 'documents.notaries.delete', 'display_name' => 'Cartórios (Excluir)'],
        ['name' => 'documents.contract_types.view', 'display_name' => 'Tipos de Contrato (Visualizar)'],
        ['name' => 'documents.contract_types.edit', 'display_name' => 'Tipos de Contrato (Editar)'],
        ['name' => 'documents.contract_types.create', 'display_name' => 'Tipos de Contrato (Criar)'],
        ['name' => 'documents.contract_types.delete', 'display_name' => 'Tipos de Contrato (Excluir)'],
        ['name' => 'documents.cost_types.view', 'display_name' => 'Tipos de Custo (Visualizar)'],
        ['name' => 'documents.cost_types.edit', 'display_name' => 'Tipos de Custo (Editar)'],
        ['name' => 'documents.cost_types.create', 'display_name' => 'Tipos de Custo (Criar)'],
        ['name' => 'documents.cost_types.delete', 'display_name' => 'Tipos de Custo (Excluir)'],
        ['name' => 'documents.property_types.view', 'display_name' => 'Tipos de Imóvel (Visualizar)'],
        ['name' => 'documents.property_types.edit', 'display_name' => 'Tipos de Imóvel (Editar)'],
        ['name' => 'documents.property_types.create', 'display_name' => 'Tipos de Imóvel (Criar)'],
        ['name' => 'documents.property_types.delete', 'display_name' => 'Tipos de Imóvel (Excluir)'],
        ['name' => 'documents.developments.view', 'display_name' => 'Empreendimentos (Visualizar)'],
        ['name' => 'documents.developments.edit', 'display_name' => 'Empreendimentos (Editar)'],
        ['name' => 'documents.developments.create', 'display_name' => 'Empreendimentos (Criar)'],
        ['name' => 'documents.developments.delete', 'display_name' => 'Empreendimentos (Excluir)'],
        ['name' => 'documents.stages.view', 'display_name' => 'Etapas da Timeline (Visualizar)'],
        ['name' => 'documents.stages.edit', 'display_name' => 'Etapas da Timeline (Editar)'],
        ['name' => 'documents.stages.create', 'display_name' => 'Etapas da Timeline (Criar)'],
        ['name' => 'documents.stages.delete', 'display_name' => 'Etapas da Timeline (Excluir)'],
        ['name' => 'documents.billable_services.view', 'display_name' => 'Serviços Cobráveis (Visualizar)'],
        ['name' => 'documents.billable_services.edit', 'display_name' => 'Serviços Cobráveis (Editar)'],
        ['name' => 'documents.billable_services.create', 'display_name' => 'Serviços Cobráveis (Criar)'],
        ['name' => 'documents.billable_services.delete', 'display_name' => 'Serviços Cobráveis (Excluir)'],
        ['name' => 'documents.proposal_documents.view', 'display_name' => 'Documentos da Proposta (Visualizar)'],
        ['name' => 'documents.proposal_documents.edit', 'display_name' => 'Documentos da Proposta (Editar)'],
        ['name' => 'documents.proposal_documents.create', 'display_name' => 'Documentos da Proposta (Criar)'],
        ['name' => 'documents.proposal_documents.delete', 'display_name' => 'Documentos da Proposta (Excluir)'],
        ['name' => 'documents.proposal_timeline.view', 'display_name' => 'Acompanhamento da Proposta (Visualizar)'],
        ['name' => 'documents.proposal_timeline.edit', 'display_name' => 'Acompanhamento da Proposta (Editar)'],
        ['name' => 'documents.proposal_timeline.create', 'display_name' => 'Acompanhamento da Proposta (Criar)'],
        ['name' => 'documents.proposal_timeline.delete', 'display_name' => 'Acompanhamento da Proposta (Excluir)'],
        ['name' => 'documents.proposal_financial.view', 'display_name' => 'Pagamentos e Recibos da Proposta (Visualizar)'],
        ['name' => 'documents.proposal_financial.edit', 'display_name' => 'Pagamentos e Recibos da Proposta (Editar)'],
        ['name' => 'documents.proposal_financial.create', 'display_name' => 'Pagamentos e Recibos da Proposta (Criar)'],
        ['name' => 'documents.proposal_financial.delete', 'display_name' => 'Pagamentos e Recibos da Proposta (Excluir)'],
        ['name' => 'documents.quotes.view', 'display_name' => 'Orçamentos (Visualizar)'],
        ['name' => 'documents.quotes.edit', 'display_name' => 'Orçamentos (Editar)'],
        ['name' => 'documents.quotes.create', 'display_name' => 'Orçamentos (Criar)'],
        ['name' => 'documents.quotes.delete', 'display_name' => 'Orçamentos (Excluir)'],
        ['name' => 'documents.itbi_municipalities.view', 'display_name' => 'ITBI - Municípios (Visualizar)'],
        ['name' => 'documents.itbi_municipalities.edit', 'display_name' => 'ITBI - Municípios (Editar)'],
        ['name' => 'documents.itbi_municipalities.create', 'display_name' => 'ITBI - Municípios (Criar)'],
        ['name' => 'documents.itbi_municipalities.delete', 'display_name' => 'ITBI - Municípios (Excluir)'],

        ['name' => 'settings.company.view', 'display_name' => 'Empresa (Visualizar)'],
        ['name' => 'settings.company.edit', 'display_name' => 'Empresa (Editar)'],
        ['name' => 'settings.roles.view', 'display_name' => 'Cargos (Visualizar)'],
        ['name' => 'settings.roles.edit', 'display_name' => 'Cargos (Editar)'],
        ['name' => 'settings.roles.create', 'display_name' => 'Cargos (Criar)'],
        ['name' => 'settings.roles.delete', 'display_name' => 'Cargos (Excluir)'],
        ['name' => 'settings.users.view', 'display_name' => 'Usuários (Visualizar)'],
        ['name' => 'settings.users.edit', 'display_name' => 'Usuários (Editar)'],
        ['name' => 'settings.users.create', 'display_name' => 'Usuários (Criar)'],
        ['name' => 'settings.users.delete', 'display_name' => 'Usuários (Excluir)'],

        ['name' => 'drive.drives.view', 'display_name' => 'Drives (Visualizar)'],
        ['name' => 'drive.drives.edit', 'display_name' => 'Drives (Editar)'],
        ['name' => 'drive.drives.create', 'display_name' => 'Drives (Criar)'],
        ['name' => 'drive.drives.delete', 'display_name' => 'Drives (Excluir)'],
        ['name' => 'drive.folders.view', 'display_name' => 'Pastas (Visualizar)'],
        ['name' => 'drive.folders.edit', 'display_name' => 'Pastas (Editar)'],
        ['name' => 'drive.folders.create', 'display_name' => 'Pastas (Criar)'],
        ['name' => 'drive.folders.delete', 'display_name' => 'Pastas (Excluir)'],
        ['name' => 'drive.trash.view', 'display_name' => 'Lixeira (Visualizar)'],
        ['name' => 'drive.trash.edit', 'display_name' => 'Lixeira (Editar)'],
        ['name' => 'drive.trash.create', 'display_name' => 'Lixeira (Criar)'],
        ['name' => 'drive.trash.delete', 'display_name' => 'Lixeira (Excluir)'],
        ['name' => 'drive.logs.view', 'display_name' => 'Logs (Visualizar)'],
        ['name' => 'drive.logs.edit', 'display_name' => 'Logs (Editar)'],
        ['name' => 'drive.logs.create', 'display_name' => 'Logs (Criar)'],
        ['name' => 'drive.logs.delete', 'display_name' => 'Logs (Excluir)'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $moduleIds = DB::table('modules')->pluck('id', 'slug');

        $existing = DB::table('permissions')
            ->whereIn('name', array_column($this->permissions, 'name'))
            ->pluck('name')
            ->all();

        $now = now();

        $rows = collect($this->permissions)
            ->reject(fn (array $permission) => in_array($permission['name'], $existing, true))
            ->map(fn (array $permission) => [
                'module_id' => $moduleIds[strstr($permission['name'], '.', true)],
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('permissions')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('name', array_column($this->permissions, 'name'))->delete();
    }
};
