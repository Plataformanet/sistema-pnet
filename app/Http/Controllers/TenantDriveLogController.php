<?php

namespace App\Http\Controllers;

use App\Services\DriveLogService;
use Inertia\Inertia;

class TenantDriveLogController extends Controller
{
    public function __construct(protected DriveLogService $driveLogService) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $logs = [];
        $logsAll = $this->driveLogService->findAll(tenant());

        foreach ($logsAll as $log) {
            // O cast 'log' => 'array' no model já retorna o array desserializado
            $logs[] = $log->log;
        }

        return Inertia::render('tenant/drive/logs/List', [
            'logs' => $logs,
        ]);
    }
}
