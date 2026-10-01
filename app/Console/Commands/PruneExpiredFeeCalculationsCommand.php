<?php

namespace App\Console\Commands;

use App\Models\FeeCalculation;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Throwable;

/**
 * Remove os cálculos de emolumentos expirados de cada tenant. O `model:prune`
 * padrão roda no banco central, onde `fee_calculations` não existe.
 */
class PruneExpiredFeeCalculationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fee-calculations:prune';

    /**
     * @var string
     */
    protected $description = 'Remove os cálculos de emolumentos expirados de todos os tenants';

    public function handle(): int
    {
        $total = 0;

        Tenant::query()->each(function (Tenant $tenant) use (&$total) {
            try {
                $total += $tenant->run(fn () => (new FeeCalculation)->pruneAll());
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Falha ao limpar o tenant {$tenant->getTenantKey()}: {$exception->getMessage()}");
            }
        });

        $this->info("Cálculos removidos: {$total}");

        return self::SUCCESS;
    }
}
