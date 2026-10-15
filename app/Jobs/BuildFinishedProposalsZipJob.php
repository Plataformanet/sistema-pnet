<?php

namespace App\Jobs;

use App\Mail\FinishedProposalsZipReadyMail;
use App\Models\User;
use App\Services\Reports\ReportZipBuilder;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File as HttpFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Gera na fila o ZIP do relatório acima do limite síncrono, grava no disco
 * privado sob a pasta do usuário e envia por e-mail o link assinado. A fila
 * restaura o tenant de origem (QueueTenancyBootstrapper).
 */
class BuildFinishedProposalsZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const BASE_PATH = 'reports';

    public int $tries = 2;

    public int $timeout = 900;

    /**
     * @param  array<int, int>  $proposalIds  já filtrados pelo critério do relatório
     */
    public function __construct(
        public int $userId,
        public array $proposalIds,
        public string $fileName,
        public string $downloadUrl,
    ) {}

    /**
     * Caminho do ZIP no disco privado: a pasta do usuário é o que impede um
     * link encaminhado de baixar o arquivo de outra pessoa.
     */
    public static function storagePath(int $userId, string $fileName): string
    {
        return self::BASE_PATH.'/'.$userId.'/'.$fileName;
    }

    public function handle(ReportService $reportService, ReportZipBuilder $reportZipBuilder): void
    {
        $tenant = tenant();
        $user = User::findOrFail($this->userId);
        $proposals = $reportService->forDownload(['proposal_ids' => $this->proposalIds], $user, $tenant);

        $zipPath = $reportZipBuilder->build($proposals, $user);

        try {
            Storage::disk(config('bucket.disk'))->putFileAs(
                self::BASE_PATH.'/'.$this->userId,
                new HttpFile($zipPath),
                $this->fileName,
            );
        } finally {
            File::delete($zipPath);
        }

        Mail::to($user->email)->queue(new FinishedProposalsZipReadyMail(
            $user->name,
            $this->downloadUrl,
            $proposals->count(),
            (int) config('reports.zip_link_ttl_hours'),
        ));
    }
}
