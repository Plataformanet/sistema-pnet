<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForceDeleteRequest;
use App\Http\Requests\ForceDeleteTrashRequest;
use App\Http\Requests\RestoreDriveRequest;
use App\Models\DriveFolder;
use App\Services\DriveService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantDriveTrashController extends Controller
{
    public function __construct(protected DriveService $driveService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $folder_id = request('folder_id');

        if ($folder_id) {
            // A pasta está na lixeira (soft-deleted); precisa incluir os trashed.
            $folders = DriveFolder::withTrashed()->findOrFail($folder_id);
        }

        if ($folder_id) {
            $drives = $this->driveService->findByTrashFolder($folder_id, tenant());
        } else {
            $drives = $this->driveService->findByTrash(tenant());
        }

        return Inertia::render(
            'tenant/drive/trash/List',
            [
                'drives' => $drives,
                'folders' => $folder_id ? $folders->breadcrumb->toArray() : [],
            ]
        );
    }

    /**
     * Restore the specified resource in storage.
     */
    public function restore(RestoreDriveRequest $request)
    {
        try {

            $this->driveService->restore($request->validated('id'), $request->validated('tipo_drive'), tenant());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Documento ou Pasta restaurado com sucesso!',
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error ao tentar restaurar o documento ou a pasta:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Erro ao tentar restaurar o documento ou a pasta!',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ForceDeleteRequest $request)
    {
        try {
            $this->driveService->forceDelete($request, tenant());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Documento ou Pasta deletado com sucesso!',
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error ao tentar deletar o documento ou a pasta:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Erro ao tentar deletar o documento ou a pasta!',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function clearTrash(ForceDeleteTrashRequest $request)
    {
        try {
            $this->driveService->clearTrash($request, tenant());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Arquivos da lixeira excluidos com sucesso!',
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error ao tentar excluir arquivos da lixeira:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error ao tentar excluir arquivos da lixeira!',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
