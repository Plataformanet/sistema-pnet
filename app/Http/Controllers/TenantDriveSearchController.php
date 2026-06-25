<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchDriveRequest;
use App\Services\DriveService;
use Inertia\Inertia;

class TenantDriveSearchController extends Controller
{
    public function __construct(protected DriveService $driveService) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(SearchDriveRequest $request)
    {
        $query = $request->validated('query');

        $drives = $this->driveService->search($query, tenant());

        return Inertia::render('tenant/drive/list/List', [
            'drives' => $drives,
            'folders' => [],
        ]);
    }
}
