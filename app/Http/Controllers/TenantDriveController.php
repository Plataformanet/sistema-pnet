<?php

namespace App\Http\Controllers;

use App\Exceptions\UploadDocumentException;
use App\Http\Requests\DeleteSelectedDriveRequest;
use App\Http\Requests\StoreAccessPermissionDriveRequest;
use App\Http\Requests\StoreDriveRequest;
use App\Http\Requests\UpdateDriveRequest;
use App\Models\DriveFolder;
use App\Services\DriveService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantDriveController extends Controller
{
    public function __construct(protected DriveService $driveService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drive_id = request('my-drive');
        $folder_id = request('folder_id');

        if ($folder_id) {
            $folders = DriveFolder::findOrFail($folder_id);

            Gate::authorize('viewFolder', $folders);
        }

        if ($drive_id && $folder_id) {
            $drives = $this->driveService->findByFolder($folder_id, tenant());
        } else {
            $drives = $this->driveService->findAll(tenant());
        }

        return Inertia::render('tenant/drive/list/List', [
            'drives' => $drives,
            'folders' => $folder_id ? $folders->breadcrumb->toArray() : [],
        ]);
    }

    /**
     * Serve o documento para download (stream ou URL assinada, conforme config).
     */
    public function download(string $id)
    {
        return $this->driveService->download($id, tenant());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDriveRequest $request)
    {
        try {
            $this->driveService->store($request, tenant());

            return response()->json(['success' => 'Upload realizado com sucesso!'], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error('Error ao tentar fazer upload do documento:', [$th->getMessage()]);
            throw new UploadDocumentException('Erro ao tentar fazer upload do documento', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the form for creating a new resource.
     */
    public function update(UpdateDriveRequest $request)
    {
        try {
            $this->driveService->update($request, tenant());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Nome do documento ou pasta atualizado com sucesso!',
                ],
                Response::HTTP_CREATED,
            );
        } catch (\Throwable $th) {
            Log::error('Error ao tentar fazer atualização do documento ou da pasta:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Erro ao atualizar documento ou pasta!',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->driveService->delete($id, tenant());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Documento ou pasta deletado com sucesso!',
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            Log::error('Error ao tentar deletar documento ou pasta:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Erro ao tentar deletar o documento ou pasta!',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function deleteSelected(DeleteSelectedDriveRequest $request)
    {
        try {
            $this->driveService->deleteSelected($request->validated('selectedValues'), tenant());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Documento ou pasta deletado com sucesso!',
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            Log::error('Error ao tentar deletar documento ou pasta:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Erro ao tentar deletar o documento ou pasta!',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function storeAccessPermissions(StoreAccessPermissionDriveRequest $request)
    {
        try {

            $this->driveService->storeAccessPermissions($request, tenant());

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Permissões salvas com sucesso!',
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            Log::error('Error ao tentar inserir permissões para usuários:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Erro ao tentar inserir permissão para o usuário!',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function userAccess(string $id)
    {
        try {

            return $this->driveService->userAccess($id, tenant());

        } catch (\Throwable $th) {
            Log::error('Error ao tentar carregar para usuários:', [$th->getMessage()]);

            return response()->json(
                [
                    'success' => false,
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function removeUserAccess(string $drive_id, string $user_id)
    {
        try {
            $this->driveService->removeUserAccess($drive_id, $user_id, tenant());

            return redirect()->back()->with(['msg_success' => 'Retirada permissão com sucesso']);
        } catch (\Throwable $th) {
            Log::error('Error ao tentar retirar acesso do usuário:', [$th->getMessage()]);

            return redirect()->back()->with(['msg_erro' => 'Erro ao retirar a permissão do usuário']);
        }
    }
}
