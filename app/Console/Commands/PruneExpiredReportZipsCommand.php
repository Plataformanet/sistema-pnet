<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ReportService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Apaga, em cada tenant, os ZIPs do relatório de propostas finalizadas cujo
 * link assinado já expirou (ver `ReportService::pruneExpiredZips`).
 */
class PruneExpiredReportZipsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reports:prune-zips';

    /**
     * @var string
     */
    protected $description = 'Remove os ZIPs expirados do relatório de propostas finalizadas de todos os tenants';

    public function handle(ReportService $reportService): int
    {
        $total = 0;

        Tenant::query()->each(function (Tenant $tenant) use (&$total, $reportService) {
            try {
                $total += $reportService->pruneExpiredZips($tenant);
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Falha ao limpar o tenant {$tenant->getTenantKey()}: {$exception->getMessage()}");
            }
        });

        $this->info("ZIPs removidos: {$total}");

        return self::SUCCESS;
    }
}
