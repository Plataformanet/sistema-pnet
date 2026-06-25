<?php

namespace App\Http\Controllers;

use App\Services\DriveService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantDriveSearchController extends Controller
{
    public function __construct(public DriveService $driveService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $query = $request->input('query');

        $drives = $this->driveService->search($query, tenant());

        return Inertia::render('tenant/drive/list/List', [
            'drives'  => $drives,
            'folders' => [],
        ]);
    }
}
