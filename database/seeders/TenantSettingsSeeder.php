<?php

namespace Database\Seeders;

use App\Models\TenantSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // === APLICAÇÃO ===
            [
                'key'         => 'app.name',
                'value'       => 'Minha Empresa',
                'type'        => 'string',
                'module'      => null,
                'is_public'   => true,
                'description' => 'Nome da empresa exibido no sistema',
            ],
            [
                'key'         => 'app.timezone',
                'value'       => 'America/Sao_Paulo',
                'type'        => 'string',
                'module'      => null,
                'is_public'   => true,
                'description' => 'Fuso horário padrão do sistema',
            ],
            [
                'key'         => 'app.locale',
                'value'       => 'pt_BR',
                'type'        => 'string',
                'module'      => null,
                'is_public'   => true,
                'description' => 'Idioma padrão da interface',
            ],
            [
                'key'         => 'app.date_format',
                'value'       => 'd/m/Y',
                'type'        => 'string',
                'module'      => null,
                'is_public'   => true,
                'description' => 'Formato de exibição de datas',
            ],

            // === FINANCEIRO ===
            [
                'key'         => 'financial.default_currency',
                'value'       => 'BRL',
                'type'        => 'string',
                'module'      => 'financial',
                'is_public'   => true,
                'description' => 'Moeda padrão (BRL, USD, EUR)',
            ],
            [
                'key'         => 'financial.fiscal_year_start',
                'value'       => '1',
                'type'        => 'integer',
                'module'      => 'financial',
                'is_public'   => false,
                'description' => 'Mês de início do ano fiscal (1-12)',
            ],
            [
                'key'         => 'financial.enable_bank_integration',
                'value'       => 'false',
                'type'        => 'boolean',
                'module'      => 'financial',
                'is_public'   => false,
                'description' => 'Habilitar integração bancária automática',
            ],

            // === DRIVE ===
            [
                'key'         => 'drive.max_file_size_mb',
                'value'       => '100',
                'type'        => 'integer',
                'module'      => 'drive',
                'is_public'   => true,
                'description' => 'Tamanho máximo de arquivo em MB',
            ],
            [
                'key'         => 'drive.allowed_extensions',
                'value'       => json_encode([
                    'pdf',
                    'doc',
                    'docx',
                    'xls',
                    'xlsx',
                    'jpg',
                    'jpeg',
                    'png',
                    'gif',
                    'zip',
                    'rar',
                    'txt'
                ]),
                'type'        => 'array',
                'module'      => 'drive',
                'is_public'   => true,
                'description' => 'Extensões de arquivo permitidas',
            ],
            [
                'key'         => 'drive.enable_public_sharing',
                'value'       => 'true',
                'type'        => 'boolean',
                'module'      => 'drive',
                'is_public'   => false,
                'description' => 'Permitir compartilhamento público de arquivos',
            ],

            // === DOCUMENTAÇÕES ===
            [
                'key'         => 'documents.allow_public_docs',
                'value'       => 'false',
                'type'        => 'boolean',
                'module'      => 'documents',
                'is_public'   => false,
                'description' => 'Permitir documentos públicos (sem login)',
            ],
            [
                'key'         => 'documents.default_editor',
                'value'       => 'wysiwyg',
                'type'        => 'string',
                'module'      => 'documents',
                'is_public'   => true,
                'description' => 'Editor padrão: markdown ou wysiwyg',
            ],

            // === SEGURANÇA ===
            [
                'key'         => 'security.password_min_length',
                'value'       => '8',
                'type'        => 'integer',
                'module'      => null,
                'is_public'   => true,
                'description' => 'Tamanho mínimo de senha',
            ],
            [
                'key'         => 'security.require_2fa',
                'value'       => 'false',
                'type'        => 'boolean',
                'module'      => null,
                'is_public'   => false,
                'description' => 'Exigir autenticação de dois fatores',
            ],
            [
                'key'         => 'security.session_timeout_minutes',
                'value'       => '120',
                'type'        => 'integer',
                'module'      => null,
                'is_public'   => false,
                'description' => 'Tempo de inatividade até logout automático',
            ],

            // === NOTIFICAÇÕES ===
            [
                'key'         => 'notifications.email_enabled',
                'value'       => 'true',
                'type'        => 'boolean',
                'module'      => null,
                'is_public'   => false,
                'description' => 'Enviar notificações por email',
            ],
            [
                'key'         => 'notifications.digest_frequency',
                'value'       => 'daily',
                'type'        => 'string',
                'module'      => null,
                'is_public'   => false,
                'description' => 'Frequência do resumo: daily, weekly, never',
            ],

            // === API ===
            [
                'key'         => 'api.rate_limit_per_minute',
                'value'       => '60',
                'type'        => 'integer',
                'module'      => 'api',
                'is_public'   => false,
                'description' => 'Requisições permitidas por minuto',
            ],
            [
                'key'         => 'api.enable_logging',
                'value'       => 'true',
                'type'        => 'boolean',
                'module'      => 'api',
                'is_public'   => false,
                'description' => 'Registrar histórico de chamadas API',
            ],
        ];

        foreach ($defaultSettings as $setting) {
            TenantSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

