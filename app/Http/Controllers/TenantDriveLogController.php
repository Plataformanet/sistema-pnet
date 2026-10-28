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
        $logs = $this->driveLogService->permanentDeletions(tenant());

        return Inertia::render('tenant/drive/logs/List', [
            'logs' => $logs,
        ]);
    }
}
