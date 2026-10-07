<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Configurações padrão do tenant. Substitui o TenantSettingsSeeder.
 * Idempotente: só insere as chaves que o tenant ainda não tem, sem sobrescrever
 * valores já ajustados.
 */
return new class extends Migration
{
    /**
     * @return array<int, array{key: string, value: string, type: string, module: string|null, is_public: bool, description: string}>
     */
    private function settings(): array
    {
        return [
            // Aplicação
            ['key' => 'app.name', 'value' => 'Minha Empresa', 'type' => 'string', 'module' => null, 'is_public' => true, 'description' => 'Nome da empresa exibido no sistema'],
            ['key' => 'app.timezone', 'value' => 'America/Sao_Paulo', 'type' => 'string', 'module' => null, 'is_public' => true, 'description' => 'Fuso horário padrão do sistema'],
            ['key' => 'app.locale', 'value' => 'pt_BR', 'type' => 'string', 'module' => null, 'is_public' => true, 'description' => 'Idioma padrão da interface'],
            ['key' => 'app.date_format', 'value' => 'd/m/Y', 'type' => 'string', 'module' => null, 'is_public' => true, 'description' => 'Formato de exibição de datas'],

            // Financeiro
            ['key' => 'financial.default_currency', 'value' => 'BRL', 'type' => 'string', 'module' => 'financial', 'is_public' => true, 'description' => 'Moeda padrão (BRL, USD, EUR)'],
            ['key' => 'financial.fiscal_year_start', 'value' => '1', 'type' => 'integer', 'module' => 'financial', 'is_public' => false, 'description' => 'Mês de início do ano fiscal (1-12)'],
            ['key' => 'financial.enable_bank_integration', 'value' => 'false', 'type' => 'boolean', 'module' => 'financial', 'is_public' => false, 'description' => 'Habilitar integração bancária automática'],

            // Drive
            ['key' => 'drive.max_file_size_mb', 'value' => '100', 'type' => 'integer', 'module' => 'drive', 'is_public' => true, 'description' => 'Tamanho máximo de arquivo em MB'],
            [
                'key' => 'drive.allowed_extensions',
                'value' => json_encode(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'txt']),
                'type' => 'array',
                'module' => 'drive',
                'is_public' => true,
                'description' => 'Extensões de arquivo permitidas',
            ],
            ['key' => 'drive.enable_public_sharing', 'value' => 'true', 'type' => 'boolean', 'module' => 'drive', 'is_public' => false, 'description' => 'Permitir compartilhamento público de arquivos'],

            // Documentações
            ['key' => 'documents.allow_public_docs', 'value' => 'false', 'type' => 'boolean', 'module' => 'documents', 'is_public' => false, 'description' => 'Permitir documentos públicos (sem login)'],
            ['key' => 'documents.default_editor', 'value' => 'wysiwyg', 'type' => 'string', 'module' => 'documents', 'is_public' => true, 'description' => 'Editor padrão: markdown ou wysiwyg'],

            // Segurança
            ['key' => 'security.password_min_length', 'value' => '8', 'type' => 'integer', 'module' => null, 'is_public' => true, 'description' => 'Tamanho mínimo de senha'],
            ['key' => 'security.require_2fa', 'value' => 'false', 'type' => 'boolean', 'module' => null, 'is_public' => false, 'description' => 'Exigir autenticação de dois fatores'],
            ['key' => 'security.session_timeout_minutes', 'value' => '120', 'type' => 'integer', 'module' => null, 'is_public' => false, 'description' => 'Tempo de inatividade até logout automático'],

            // Notificações
            ['key' => 'notifications.email_enabled', 'value' => 'true', 'type' => 'boolean', 'module' => null, 'is_public' => false, 'description' => 'Enviar notificações por email'],
            ['key' => 'notifications.digest_frequency', 'value' => 'daily', 'type' => 'string', 'module' => null, 'is_public' => false, 'description' => 'Frequência do resumo: daily, weekly, never'],

            // API
            ['key' => 'api.rate_limit_per_minute', 'value' => '60', 'type' => 'integer', 'module' => 'api', 'is_public' => false, 'description' => 'Requisições permitidas por minuto'],
            ['key' => 'api.enable_logging', 'value' => 'true', 'type' => 'boolean', 'module' => 'api', 'is_public' => false, 'description' => 'Registrar histórico de chamadas API'],
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = $this->settings();

        $existing = DB::table('tenant_settings')
            ->whereIn('key', array_column($settings, 'key'))
            ->pluck('key')
            ->all();

        $now = now();

        $rows = collect($settings)
            ->reject(fn (array $setting) => in_array($setting['key'], $existing, true))
            ->map(fn (array $setting) => [...$setting, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('tenant_settings')->insert($rows);
        }
    }

    /**
     * Não remove as configurações: o tenant pode ter ajustado os valores.
     */
    public function down(): void
    {
        //
    }
};
