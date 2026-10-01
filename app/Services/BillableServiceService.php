<?php

namespace App\Services;

use App\Models\BillableService;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class BillableServiceService extends CatalogService
{
    protected function getModel(): string
    {
        return BillableService::class;
    }

    protected function usageRelations(): array
    {
        return ['proposals', 'quotes'];
    }

    /**
     * Serviços escolhidos com o valor efetivo: o negociado, quando informado,
     * ou o preço de tabela. Serviços excluídos não podem ser escolhidos.
     *
     * @param  array<int, array{id: int|string, amount?: int|null}>  $selection
     * @return Collection<int, array{service: BillableService, amount: int}>
     */
    public function resolveSelection(array $selection, Tenant $tenant): Collection
    {
        return $tenant->run(function () use ($selection) {
            $services = BillableService::whereKey(array_column($selection, 'id'))->get()->keyBy('id');

            return collect($selection)
                ->filter(fn (array $item) => $services->has((int) $item['id']))
                ->unique(fn (array $item) => (int) $item['id'])
                ->map(fn (array $item) => [
                    'service' => $services->get((int) $item['id']),
                    'amount' => isset($item['amount']) && $item['amount'] !== null
                        ? (int) $item['amount']
                        : $services->get((int) $item['id'])->price,
                ])
                ->values();
        });
    }
}
