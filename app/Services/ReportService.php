<?php

namespace App\Services;

use App\Jobs\BuildFinishedProposalsZipJob;
use App\Models\Applicant;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use App\Queries\FinishedProposalsReportQuery;
use App\Services\Reports\ReportZipBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use SplFileInfo;

/**
 * Relatório de propostas finalizadas. Só leitura: parte de `proposals`
 * (status "Finalizada"), com eager loading em vez de joins, e calcula a data
 * de finalização no banco para filtrar e ordenar por ela.
 */
class ReportService
{
    public const PER_PAGE = 25;

    public function __construct(
        protected FinishedProposalsReportQuery $finishedProposalsReportQuery,
    ) {}

    /**
     * @param  array{start_date?: string|null, end_date?: string|null, development_id?: int|string|null}  $filters
     */
    public function finishedProposals(array $filters, User $user, Tenant $tenant): LengthAwarePaginator
    {
        return $tenant->run(fn () => $this->finishedProposalsReportQuery->build($filters, $user)
            ->with([
                'creator:id,name',
                'applicants:id,contact_id',
                'applicants.contact:id,name_corporatereason',
                'property:id,proposal_id,development_id',
                'property.development:id,name,deleted_at',
                'finalStage.document:id,proposal_id,title,original_name,mime_type,size',
            ])
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Proposal $proposal) => $this->listRow($proposal)));
    }

    /**
     * Quantos documentos finais o filtro inteiro tem (opção "Selecionar todos
     * os N resultados").
     *
     * @param  array{start_date?: string|null, end_date?: string|null, development_id?: int|string|null}  $filters
     */
    public function downloadableCount(array $filters, User $user, Tenant $tenant): int
    {
        return $tenant->run(fn () => $this->finishedProposalsReportQuery->build($filters, $user)
            ->whereHas('finalStage', fn (Builder $query) => $query->whereNotNull('proposal_document_id'))
            ->count());
    }

    /**
     * Propostas do ZIP: os ids marcados ou todo o filtro (`all_matching`).
     * Sempre reaplica o critério de finalizada, então ids fora dele são
     * ignorados, assim como propostas sem documento na etapa final. O
     * `$limit` evita carregar um filtro enorme só para recusá-lo pelo teto.
     *
     * @param  array{all_matching?: bool|string|null, proposal_ids?: array<int, int|string>, start_date?: string|null, end_date?: string|null, development_id?: int|string|null}  $selection
     * @return Collection<int, Proposal>
     */
    public function forDownload(array $selection, User $user, Tenant $tenant, ?int $limit = null): Collection
    {
        $filters = filter_var($selection['all_matching'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? Arr::except($selection, 'proposal_ids')
            : ['proposal_ids' => $selection['proposal_ids'] ?? []];

        return $tenant->run(fn () => $this->finishedProposalsReportQuery->build($filters, $user)
            ->when(array_key_exists('proposal_ids', $filters), fn (Builder $query) => $query->whereKey($filters['proposal_ids']))
            ->whereHas('finalStage', fn (Builder $query) => $query->whereNotNull('proposal_document_id'))
            ->with('finalStage.document')
            ->when($limit, fn (Builder $query, int $limit) => $query->limit($limit))
            ->get());
    }

    /**
     * Apaga os ZIPs do relatório cujo link já expirou: os gerados na fila
     * (pasta `reports/` do disco privado) e os temporários que uma requisição
     * interrompida deixou em `storage/app/tmp`. São documentos pessoais, então
     * não ficam guardados além do necessário.
     *
     * @return int quantidade de arquivos apagados
     */
    public function pruneExpiredZips(Tenant $tenant): int
    {
        $expiredBefore = now()->subHours((int) config('reports.zip_link_ttl_hours'))->getTimestamp();

        return $tenant->run(function () use ($expiredBefore) {
            $disk = Storage::disk(config('bucket.disk'));

            $storedZips = collect($disk->allFiles(BuildFinishedProposalsZipJob::BASE_PATH))
                ->filter(fn (string $path) => str_ends_with($path, '.zip') && $disk->lastModified($path) < $expiredBefore)
                ->values()
                ->all();

            if ($storedZips !== []) {
                $disk->delete($storedZips);
            }

            $temporaryDirectory = storage_path(ReportZipBuilder::TMP_PATH);
            $temporaryFiles = File::isDirectory($temporaryDirectory)
                ? collect(File::files($temporaryDirectory))
                    ->filter(fn (SplFileInfo $file) => $file->getMTime() < $expiredBefore)
                    ->map(fn (SplFileInfo $file) => $file->getPathname())
                    ->values()
                    ->all()
                : [];

            File::delete($temporaryFiles);

            return count($storedZips) + count($temporaryFiles);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function listRow(Proposal $proposal): array
    {
        $document = $proposal->finalStage?->document;

        return [
            'id' => $proposal->id,
            'number' => $proposal->number,
            'creator' => $proposal->creator?->name,
            'applicants' => $proposal->applicants
                ->map(fn (Applicant $applicant) => $applicant->contact?->name_corporatereason)
                ->filter()
                ->values()
                ->all(),
            'development' => $proposal->property?->development?->name,
            'document' => $document !== null ? ['id' => $document->id, 'title' => $document->title] : null,
            'report_date' => $proposal->getAttribute('report_date') !== null
                ? substr((string) $proposal->getAttribute('report_date'), 0, 10)
                : null,
        ];
    }
}
