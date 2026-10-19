<?php

namespace App\Services;

use App\Exceptions\StageOrderTakenException;
use App\Models\Bank;
use App\Models\Stage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Etapas das timelines: a padrão do sistema (`bank_id` nulo) e as
 * personalizadas por banco. Ordem, reordenação e restauração valem sempre
 * dentro da timeline da própria etapa.
 */
class StageService extends CatalogService
{
    public const DIRECTION_UP = 'up';

    public const DIRECTION_DOWN = 'down';

    public const DEFAULT_TIMELINE_NAME = 'Padrão do sistema';

    protected function getModel(): string
    {
        return Stage::class;
    }

    /**
     * A listagem mostra uma timeline por vez; sem `bank_id`, a padrão.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function scopeListing(Builder $query, array $filters): Builder
    {
        return $query->forTimeline(isset($filters['bank_id']) ? (int) $filters['bank_id'] : null);
    }

    public function findById(string $id, Tenant $tenant): Model
    {
        return $tenant->run(fn () => Stage::with('bank:id,name')->findOrFail($id));
    }

    /**
     * Cria a etapa travando a timeline: o Form Request valida a ordem única,
     * mas dois cadastros simultâneos poderiam passar juntos pela validação, e
     * duas etapas com a mesma ordem fazem a timeline pular uma delas. Como a
     * ordem pode repetir em etapas excluídas, não há índice único no banco.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws StageOrderTakenException quando a ordem foi ocupada nesse meio-tempo.
     */
    public function store(array $data, Tenant $tenant): Model
    {
        return $tenant->run(fn () => DB::transaction(function () use ($data) {
            $timeline = Stage::forTimeline(isset($data['bank_id']) ? (int) $data['bank_id'] : null)
                ->lockForUpdate()
                ->get(['id', 'order']);

            if ($timeline->contains('order', (int) $data['order'])) {
                throw new StageOrderTakenException('Já existe uma etapa ativa com esta ordem nesta timeline.');
            }

            return Stage::create($data);
        }));
    }

    /**
     * O banco é definido na criação e nunca muda: trocar a etapa de timeline
     * bagunçaria a ordem das duas timelines.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, string $id, Tenant $tenant): bool
    {
        return parent::update(Arr::except($data, 'bank_id'), $id, $tenant);
    }

    /**
     * Banco da timeline da etapa (inclusive excluída), para voltar à listagem
     * da mesma timeline depois de uma ação.
     */
    public function timelineBankId(string $id, Tenant $tenant): ?int
    {
        return $tenant->run(fn () => Stage::withTrashed()->findOrFail($id)->bank_id);
    }

    /**
     * Banco ativo que recebe etapas novas; `null` para a timeline padrão.
     */
    public function findTimelineBank(?int $bankId, Tenant $tenant): ?Bank
    {
        return $bankId === null ? null : $tenant->run(fn () => Bank::findOrFail($bankId));
    }

    /**
     * Opções do seletor de timeline: a padrão do sistema e os bancos ativos,
     * com a quantidade de etapas ativas de cada um (0 = usa a padrão). O banco
     * selecionado entra mesmo se excluído, sinalizado no nome, para continuar
     * visível.
     *
     * @return array<int, array{bank_id: int|null, name: string, stages_count: int}>
     */
    public function timelineOptions(Tenant $tenant, ?int $currentBankId = null): array
    {
        return $tenant->run(function () use ($currentBankId) {
            $banks = Bank::query()
                ->when($currentBankId, fn ($query) => $query->withTrashed()->where(
                    fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $currentBankId)
                ))
                ->select(['id', 'name', 'deleted_at'])
                ->withCount('stages')
                ->ordered()
                ->get();

            return [
                ['bank_id' => null, 'name' => self::DEFAULT_TIMELINE_NAME, 'stages_count' => Stage::forTimeline(null)->count()],
                ...$banks->map(fn (Bank $bank) => [
                    'bank_id' => $bank->id,
                    'name' => $bank->trashed() ? $bank->name.' (excluído)' : $bank->name,
                    'stages_count' => $bank->stages_count,
                ])->all(),
            ];
        });
    }

    /**
     * Troca a etapa de posição com a vizinha ativa da mesma timeline, na
     * direção informada.
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
                    ->forTimeline($stage->bank_id)
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
     * Leva a etapa para a posição da etapa alvo (arrastar e soltar), deslocando
     * as que ficam entre as duas. As ordens são redistribuídas entre os mesmos
     * valores que as etapas ativas da timeline já usam, então nenhuma ordem
     * nova surge e não há colisão com etapas excluídas. Alvo de outra timeline
     * é tratado como inexistente.
     *
     * Assim como `move`, só vale para timelines instanciadas depois.
     */
    public function moveTo(string $id, string $targetId, Tenant $tenant): void
    {
        $tenant->run(function () use ($id, $targetId) {
            DB::transaction(function () use ($id, $targetId) {
                $stage = Stage::lockForUpdate()->findOrFail($id);
                $stages = Stage::query()->forTimeline($stage->bank_id)->ordered()->lockForUpdate()->get();
                $targetIndex = $stages->search(fn (Stage $item) => $item->id === (int) $targetId);

                if ($targetIndex === false) {
                    throw (new ModelNotFoundException)->setModel(Stage::class, [$targetId]);
                }

                $orders = $stages->pluck('order')->all();
                $reordered = $stages->reject(fn (Stage $item) => $item->is($stage))->values();
                $reordered->splice($targetIndex, 0, [$stage]);

                $reordered->each(function (Stage $item, int $index) use ($orders) {
                    $item->order = $orders[$index];
                    $item->save();
                });
            });
        });
    }

    /**
     * Restaura a etapa. Se, enquanto ela estava excluída, outra etapa ativa da
     * mesma timeline passou a usar a mesma ordem, a restaurada vai para o fim
     * dessa timeline: a timeline avança pela posição, e duas etapas com a mesma
     * ordem fariam uma delas ser pulada.
     */
    public function restore(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => DB::transaction(function () use ($id) {
            $stage = Stage::onlyTrashed()->lockForUpdate()->findOrFail($id);

            if (Stage::forTimeline($stage->bank_id)->where('order', $stage->order)->lockForUpdate()->exists()) {
                $stage->order = (int) Stage::forTimeline($stage->bank_id)->max('order') + 1;
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
