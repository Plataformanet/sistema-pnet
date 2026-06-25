<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriveFolderRequest;
use App\Services\DriveFolderService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantDriveFolderController extends Controller
{
    public function __construct(protected DriveFolderService $driveFolderService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $folders = $this->driveFolderService->findAll(tenant());

        return Inertia::render('tenant/drive_folder/List', [
            'folders' => $folders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('tenant/drive_folder/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDriveFolderRequest $request)
    {
        try {
            $this->driveFolderService->store($request, tenant());

            return redirect()->back()->with('msg_success', 'Pasta cadastrada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Error ao tentar criar pasta:', [$th->getMessage()]);

            return redirect()->back()->with('msg_erro', 'Erro ao tentar fazer cadastro da pasta!');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->driveFolderService->delete($id, tenant());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Pasta deletada com sucesso!',
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            Log::error('Error ao tentar deletar pasta:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Erro ao tentar deletar pasta!',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
