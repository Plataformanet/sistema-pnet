<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registra no catálogo central as permissões do módulo "Documentações", para
 * que planos com o módulo provisionem os novos tenants já com elas. Em uma
 * instalação nova o módulo ainda não existe aqui e o PermissionSeeder cuida disso.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{name: string, display_name: string}>
     */
    private array $permissions = [
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
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'documents')->value('id');

        if ($moduleId === null) {
            return;
        }

        $existing = DB::table('permissions')
            ->whereIn('name', array_column($this->permissions, 'name'))
            ->pluck('name')
            ->all();

        $now = now();

        $rows = collect($this->permissions)
            ->reject(fn (array $permission) => in_array($permission['name'], $existing, true))
            ->map(fn (array $permission) => [
                'module_id' => $moduleId,
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
