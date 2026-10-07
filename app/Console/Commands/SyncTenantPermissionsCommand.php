<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantPermissionService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sincroniza cargos e permissões de cada tenant com o plano dele, a partir do
 * catálogo central. Idempotente: só adiciona o que falta.
 */
class SyncTenantPermissionsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tenants:sync-permissions
                            {--tenants=* : IDs dos tenants (padrão: todos)}';

    /**
     * @var string
     */
    protected $description = 'Sincroniza cargos e permissões dos tenants com os módulos ativos de cada um';

    public function handle(TenantPermissionService $tenantPermissionService): int
    {
        $failed = false;

        Tenant::query()
            ->when($this->option('tenants'), fn ($query, array $ids) => $query->whereIn('id', $ids))
            ->each(function (Tenant $tenant) use ($tenantPermissionService, &$failed) {
                try {
                    $tenantPermissionService->sync($tenant);
                    $this->info("Tenant {$tenant->getTenantKey()}: permissões sincronizadas.");
                } catch (Throwable $exception) {
                    $failed = true;
                    report($exception);
                    $this->error("Falha ao sincronizar o tenant {$tenant->getTenantKey()}: {$exception->getMessage()}");
                }
            });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
