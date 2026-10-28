<?php

namespace App\Services;

use App\Enums\DriveLogActionEnum;
use App\Models\Drive;
use App\Models\DriveLog;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\LazyCollection;

class DriveLogService
{
    /**
     * Registra uma ação do Drive com o usuário logado e o IP da requisição
     * (RN-DRV-05). Chamar dentro de `$tenant->run()`, na mesma transação da ação.
     *
     * Quando há `$drive`, o nome, o caminho e o tipo dele entram em `meta`; o
     * `$meta` informado complementa (ou sobrescreve) esses dados.
     *
     * @param  array<string, mixed>  $meta
     */
    public function record(DriveLogActionEnum $action, ?Drive $drive = null, array $meta = []): DriveLog
    {
        $driveMeta = $drive === null ? [] : [
            'name' => $drive->name,
            'document_path' => $drive->document_path,
            'document_type' => $drive->document_type?->value,
        ];

        return DriveLog::create([
            'user_id' => Auth::id(),
            'drive_id' => $drive?->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'meta' => array_merge($driveMeta, $meta),
        ]);
    }

    public function findAll(Tenant $tenant): LazyCollection
    {
        return $tenant->run(function () {
            return DriveLog::cursor();
        });
    }

    /**
     * Logs de exclusão definitiva no formato da tela "Logs de Exclusão".
     * Os logs anteriores à estruturação não têm `user_id`: o nome de quem
     * excluiu vem do `meta`.
     *
     * @return Collection<int, array{name: ?string, document_path: ?string, document_type: ?string, deleted_by: ?string, deleted_at: string}>
     */
    public function permanentDeletions(Tenant $tenant): Collection
    {
        return $tenant->run(function () {
            return DriveLog::with('user:id,name')
                ->where('action', DriveLogActionEnum::PERMANENT_DELETE)
                ->orderBy('id')
                ->get()
                ->map(fn (DriveLog $log): array => [
                    'name' => $log->meta['name'] ?? null,
                    'document_path' => $log->meta['document_path'] ?? null,
                    'document_type' => $log->meta['document_type'] ?? null,
                    'deleted_by' => $log->user?->name ?? $log->meta['deleted_by'] ?? null,
                    'deleted_at' => $log->created_at->toDateTimeString(),
                ]);
        });
    }
}
