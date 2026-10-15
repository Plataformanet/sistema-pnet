<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadFinishedProposalsRequest;
use App\Http\Requests\FinishedProposalsReportRequest;
use App\Jobs\BuildFinishedProposalsZipJob;
use App\Services\DevelopmentService;
use App\Services\Reports\ReportZipBuilder;
use App\Services\ReportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TenantReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected ReportZipBuilder $reportZipBuilder,
        protected DevelopmentService $developmentService,
    ) {}

    public function finishedProposals(FinishedProposalsReportRequest $request)
    {
        $filters = $request->safe()->only(['start_date', 'end_date', 'development_id']);

        return Inertia::render('tenant/documents/reports/finished-proposals/FinishedProposals', [
            'proposals' => $this->reportService->finishedProposals($filters, $request->user(), tenant()),
            'downloadableCount' => $this->reportService->downloadableCount($filters, $request->user(), tenant()),
            'developments' => $this->developmentService->options(tenant(), $filters['development_id'] ?? null)->map->only(['id', 'name', 'deleted_at']),
            'zipLimits' => [
                'sync' => (int) config('reports.zip_sync_limit'),
                'max' => (int) config('reports.zip_max_files'),
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Até o limite síncrono devolve o ZIP na própria resposta; acima dele
     * despacha a geração para a fila e responde 202 (o link chega por e-mail).
     */
    public function downloadFinishedProposals(DownloadFinishedProposalsRequest $request)
    {
        $user = $request->user();
        $maxFiles = (int) config('reports.zip_max_files');
        $proposals = $this->reportService->forDownload($request->validated(), $user, tenant(), $maxFiles + 1);

        if ($proposals->isEmpty()) {
            throw ValidationException::withMessages(['proposal_ids' => 'Nenhum documento disponível para download.']);
        }

        if ($proposals->count() > $maxFiles) {
            throw ValidationException::withMessages(['proposal_ids' => "Selecione no máximo {$maxFiles} documentos."]);
        }

        $downloadName = 'relatorio-propostas-finalizadas-'.now()->format('Y-m-d').'.zip';

        try {
            if ($proposals->count() > (int) config('reports.zip_sync_limit')) {
                $fileName = Str::replaceLast('.zip', '-'.Str::lower(Str::random(12)).'.zip', $downloadName);

                BuildFinishedProposalsZipJob::dispatch(
                    $user->id,
                    $proposals->modelKeys(),
                    $fileName,
                    URL::temporarySignedRoute(
                        'tenant.documents.reports.downloads.show',
                        now()->addHours((int) config('reports.zip_link_ttl_hours')),
                        ['file' => $fileName],
                    ),
                );

                return response()->json([
                    'message' => "O ZIP com {$proposals->count()} documentos está sendo gerado. Você receberá o link por e-mail.",
                ], 202);
            }

            $zipPath = tenant()->run(fn () => $this->reportZipBuilder->build($proposals, $user));

            return response()->download($zipPath, $downloadName, ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
        } catch (\Throwable $th) {
            Log::error('Erro ao gerar o ZIP do relatório de propostas finalizadas: '.$th->getMessage());

            return response()->json(['message' => 'Erro ao gerar o ZIP do relatório.'], 500);
        }
    }
}
