<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class TenantCrmController extends Controller
{
    public function __construct() {}

    /**
     * Exibe o Quadro Kanban do CRM com dados demonstrativos.
     */
    public function kanban(): Response
    {
        return Inertia::render('tenant/crm/kanban/Kanban', [
            'pipelines' => $this->getMockPipelines(),
            'stages' => $this->getMockStages(),
            'metrics' => $this->getMockMetrics(),
            'deals' => $this->getMockDeals(),
        ]);
    }

    /**
     * Exibe a Listagem em Tabela do CRM com dados demonstrativos.
     */
    public function list(): Response
    {
        return Inertia::render('tenant/crm/list/List', [
            'pipelines' => $this->getMockPipelines(),
            'stages' => $this->getMockStages(),
            'metrics' => $this->getMockMetrics(),
            'deals' => $this->getMockDeals(),
        ]);
    }

    private function getMockPipelines(): array
    {
        return [
            ['id' => 1, 'name' => 'Vendas Diretas B2B', 'is_default' => true],
            ['id' => 2, 'name' => 'Leads Inbound (Site/Ads)', 'is_default' => false],
            ['id' => 3, 'name' => 'Pós-Venda & Renovações', 'is_default' => false],
        ];
    }

    private function getMockStages(): array
    {
        return [
            ['id' => 101, 'crm_pipeline_id' => 1, 'name' => 'Prospecção', 'order' => 1, 'probability' => 10, 'color' => '#64748b'],
            ['id' => 102, 'crm_pipeline_id' => 1, 'name' => 'Qualificação', 'order' => 2, 'probability' => 30, 'color' => '#3b82f6'],
            ['id' => 103, 'crm_pipeline_id' => 1, 'name' => 'Proposta Enviada', 'order' => 3, 'probability' => 60, 'color' => '#8b5cf6'],
            ['id' => 104, 'crm_pipeline_id' => 1, 'name' => 'Negociação', 'order' => 4, 'probability' => 80, 'color' => '#f59e0b'],
            ['id' => 105, 'crm_pipeline_id' => 1, 'name' => 'Fechado / Ganho', 'order' => 5, 'probability' => 100, 'color' => '#10b981'],
        ];
    }

    private function getMockMetrics(): array
    {
        return [
            'total_open_value' => 28500000, // R$ 285.000,00 em centavos
            'won_this_month' => 14200000,   // R$ 142.000,00 em centavos
            'conversion_rate' => 34.8,      // %
            'active_deals_count' => 14,
        ];
    }

    private function getMockDeals(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Implantação de ERP + Licenças Telhamar',
                'contact_id' => 10,
                'contact_name' => 'Telhamar Construtora Ltda',
                'contact_phone' => '(11) 98765-4321',
                'user_id' => 1,
                'user_name' => 'Carlos Eduardo',
                'crm_pipeline_id' => 1,
                'crm_stage_id' => 104, // Negociação
                'total_value' => 8500000, // R$ 85.000,00
                'status' => 'open',
                'priority' => 'alta',
                'expected_close_date' => '2026-08-25',
                'created_at' => '2026-08-01T10:00:00Z',
                'items' => [
                    [
                        'id' => 1001,
                        'item_type' => 'service',
                        'item_id' => 5,
                        'name' => 'Consultoria de Implantação e Treinamento',
                        'unit_price' => 5000000,
                        'quantity' => 1,
                        'discount' => 0,
                        'total_price' => 5000000,
                    ],
                    [
                        'id' => 1002,
                        'item_type' => 'product',
                        'item_id' => 12,
                        'name' => 'Licença Anual Sistema PNET Enterprise',
                        'unit_price' => 3500000,
                        'quantity' => 1,
                        'discount' => 0,
                        'total_price' => 3500000,
                    ],
                ],
                'activities' => [
                    [
                        'id' => 201,
                        'type' => 'meeting',
                        'description' => 'Reunião de apresentação da proposta comercial com a diretoria.',
                        'user_name' => 'Carlos Eduardo',
                        'created_at' => '2026-08-03T14:30:00Z',
                    ],
                    [
                        'id' => 202,
                        'type' => 'note',
                        'description' => 'Cliente solicitou desconto de 5% caso o pagamento seja à vista.',
                        'user_name' => 'Carlos Eduardo',
                        'created_at' => '2026-08-05T09:15:00Z',
                    ],
                ],
            ],
            [
                'id' => 2,
                'title' => 'Contrato Anual de Suporte TI & Infra',
                'contact_id' => 11,
                'contact_name' => 'Logística Express S.A.',
                'contact_phone' => '(19) 97123-8899',
                'user_id' => 2,
                'user_name' => 'Mariana Silva',
                'crm_pipeline_id' => 1,
                'crm_stage_id' => 103, // Proposta Enviada
                'total_value' => 4200000, // R$ 42.000,00
                'status' => 'open',
                'priority' => 'media',
                'expected_close_date' => '2026-08-30',
                'created_at' => '2026-08-02T11:20:00Z',
                'items' => [
                    [
                        'id' => 1003,
                        'item_type' => 'service',
                        'item_id' => 8,
                        'name' => 'Suporte Técnico Nivel 3 (SLA 2h)',
                        'unit_price' => 350000,
                        'quantity' => 12,
                        'discount' => 0,
                        'total_price' => 4200000,
                    ],
                ],
                'activities' => [
                    [
                        'id' => 203,
                        'type' => 'call',
                        'description' => 'Ligação para alinhamento do escopo de SLA.',
                        'user_name' => 'Mariana Silva',
                        'created_at' => '2026-08-04T16:00:00Z',
                    ],
                ],
            ],
            [
                'id' => 3,
                'title' => 'Fornecimento de Licenças adicionais (50 usuários)',
                'contact_id' => 12,
                'contact_name' => 'Soluções Agrícolas Vale Verde',
                'contact_phone' => '(16) 3344-5566',
                'user_id' => 1,
                'user_name' => 'Carlos Eduardo',
                'crm_pipeline_id' => 1,
                'crm_stage_id' => 105, // Fechado / Ganho
                'total_value' => 6000000, // R$ 60.000,00
                'status' => 'won',
                'priority' => 'alta',
                'expected_close_date' => '2026-08-06',
                'created_at' => '2026-07-28T09:00:00Z',
                'items' => [
                    [
                        'id' => 1004,
                        'item_type' => 'product',
                        'item_id' => 15,
                        'name' => 'Pacote 50 Usuários PNET SaaS',
                        'unit_price' => 6000000,
                        'quantity' => 1,
                        'discount' => 0,
                        'total_price' => 6000000,
                    ],
                ],
                'activities' => [
                    [
                        'id' => 204,
                        'type' => 'note',
                        'description' => 'Contrato assinado via Docusign. Venda ganha!',
                        'user_name' => 'Carlos Eduardo',
                        'created_at' => '2026-08-06T15:00:00Z',
                    ],
                ],
            ],
            [
                'id' => 4,
                'title' => 'Consultoria de Segurança e LGPD',
                'contact_id' => 13,
                'contact_name' => 'Farmácia Central Distribuição',
                'contact_phone' => '(11) 96543-2100',
                'user_id' => 2,
                'user_name' => 'Mariana Silva',
                'crm_pipeline_id' => 1,
                'crm_stage_id' => 102, // Qualificação
                'total_value' => 2800000, // R$ 28.000,00
                'status' => 'open',
                'priority' => 'baixa',
                'expected_close_date' => '2026-09-10',
                'created_at' => '2026-08-04T14:15:00Z',
                'items' => [],
                'activities' => [],
            ],
            [
                'id' => 5,
                'title' => 'Atualização de Servidores e Backup em Nuvem',
                'contact_id' => 14,
                'contact_name' => 'Auto Peças Global Sp',
                'contact_phone' => '(11) 3322-1100',
                'user_id' => 1,
                'user_name' => 'Carlos Eduardo',
                'crm_pipeline_id' => 1,
                'crm_stage_id' => 101, // Prospecção
                'total_value' => 7000000, // R$ 70.000,00
                'status' => 'open',
                'priority' => 'media',
                'expected_close_date' => '2026-09-15',
                'created_at' => '2026-08-06T11:00:00Z',
                'items' => [],
                'activities' => [],
            ],
        ];
    }
}
