<?php

namespace App\Http\Controllers;

use App\Jobs\BuildFinishedProposalsZipJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantReportDownloadController extends Controller
{
    /**
     * Link assinado do ZIP gerado na fila. O arquivo é procurado na pasta do
     * usuário logado: o link só funciona para quem o solicitou.
     */
    public function __invoke(Request $request, string $file)
    {
        $path = BuildFinishedProposalsZipJob::storagePath($request->user()->id, $file);

        return tenant()->run(function () use ($path, $file) {
            $disk = Storage::disk(config('bucket.disk'));

            abort_unless($disk->exists($path), 404);

            return $disk->download($path, preg_replace('/-[a-z0-9]{12}\.zip$/', '.zip', $file));
        });
    }
}
