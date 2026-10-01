<?php

namespace App\Services;

use App\Models\Stage;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class StageService extends CatalogService
{
    public const DIRECTION_UP = 'up';

    public const DIRECTION_DOWN = 'down';

    protected function getModel(): string
    {
        return Stage::class;
    }

    /**
     * Troca a etapa de posição com a vizinha ativa na direção informada.
     *
     * A ordem só vale para timelines instanciadas depois: `proposal_stages.position`
     * é uma cópia feita na criação da timeline. Na primeira/última posição não há
     * vizinha e nada é alterado.
     */
    public function move(string $id, string $direction, Tenant $tenant): void
    {
        $tenant->run(function () use ($id, $direction) {
            DB::transaction(function () use ($id, $direction) {
                $stage = Stage::lockForUpdate()->findOrFail($id);

                $neighbor = Stage::query()
                    ->lockForUpdate()
                    ->when(
                        $direction === self::DIRECTION_UP,
                        fn ($query) => $query->where('order', '<', $stage->order)->orderByDesc('order'),
                        fn ($query) => $query->where('order', '>', $stage->order)->orderBy('order'),
                    )
                    ->first();

                if ($neighbor === null) {
                    return;
                }

                [$stage->order, $neighbor->order] = [$neighbor->order, $stage->order];

                $stage->save();
                $neighbor->save();
            });
        });
    }

    /**
     * Restaura a etapa. Se, enquanto ela estava excluída, outra etapa ativa
     * passou a usar a mesma ordem, a restaurada vai para o fim: a timeline
     * avança pela posição, e duas etapas com a mesma ordem fariam uma delas
     * ser pulada.
     */
    public function restore(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => DB::transaction(function () use ($id) {
            $stage = Stage::onlyTrashed()->lockForUpdate()->findOrFail($id);

            if (Stage::where('order', $stage->order)->lockForUpdate()->exists()) {
                $stage->order = (int) Stage::max('order') + 1;
            }

            return $stage->restore();
        }));
    }

    /**
     * Indica se a etapa está em andamento em alguma proposta: as regras de
     * obrigatoriedade são lidas ao vivo na conclusão, então editá-las afeta
     * essas timelines.
     */
    public function hasOpenProposalStages(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => Stage::findOrFail($id)->proposalStages()->whereNull('completed_at')->exists());
    }

    protected function usageRelations(): array
    {
        return ['proposalStages'];
    }
}
