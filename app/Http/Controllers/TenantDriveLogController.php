<?php

namespace App\Http\Controllers;

use App\Services\DriveLogService;
use Inertia\Inertia;


class TenantDriveLogController extends Controller
{
    public function __construct(public DriveLogService $driveLogService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $logs    = [];
        $logsAll = $this->driveLogService->findAll(tenant());

        foreach ($logsAll as $log) {
            $logs[] = json_decode($log->log, true);
        }

        return Inertia::render('tenant/drive/logs/List', [
            'logs' => $logs,
        ]);
    }
}
