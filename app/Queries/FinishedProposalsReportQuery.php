<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ProposalStatusEnum;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consulta base do relatório de propostas finalizadas: propostas visíveis ao
 * usuário com status "Finalizada", filtradas e ordenadas pela data de
 * finalização calculada no banco. Deve rodar dentro de `$tenant->run()`.
 */
final class FinishedProposalsReportQuery
{
    /**
     * Data de finalização: a data informada ao concluir a última etapa da
     * timeline e, se vazia, o dia em que o sistema marcou a proposta como
     * finalizada.
     */
    private const REPORT_DATE = 'COALESCE((SELECT ps.date FROM proposal_stages ps WHERE ps.proposal_id = proposals.id ORDER BY ps.position DESC LIMIT 1), DATE(proposals.finished_at))';

    /**
     * @param  array{start_date?: string|null, end_date?: string|null, development_id?: int|string|null}  $filters
     * @return Builder<Proposal>
     */
    public function build(array $filters, User $user): Builder
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        return Proposal::query()
            ->select('proposals.*')
            ->selectRaw(self::REPORT_DATE.' AS report_date')
            ->visibleTo($user)
            ->withStatus(ProposalStatusEnum::FINISHED)
            ->when($startDate && $endDate, fn (Builder $query) => $query->whereRaw(self::REPORT_DATE.' BETWEEN ? AND ?', [$startDate, $endDate]))
            ->when($filters['development_id'] ?? null, fn (Builder $query, int|string $developmentId) => $query->whereHas(
                'property',
                fn (Builder $query) => $query->where('development_id', $developmentId),
            ))
            ->orderByRaw(self::REPORT_DATE.' DESC')
            ->orderByDesc('proposals.id');
    }
}
