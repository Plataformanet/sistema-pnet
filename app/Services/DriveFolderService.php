<?php

namespace App\Services;

use App\Enums\DocumentTypeDriveEnum;
use App\Http\Requests\StoreDriveFolderRequest;
use App\Models\DriveFolder;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DriveFolderService
{
    public function store(StoreDriveFolderRequest $request, Tenant $tenant)
    {
        return $tenant->run(function () use ($request) {
            return DB::transaction(function () use ($request) {

                $disk = Storage::disk('public');
                $requestedName = $request->validated('name');

                // Definir parent_id se houver (pasta dentro de pasta)
                $parentId = $request->validated('parent_id', null);

                // Buscar apenas pastas com o mesmo nome e parent no banco
                $existingFolder = DriveFolder::where('name', $requestedName)
                    ->where('parent_id', $parentId)
                    ->latest('id')
                    ->first();

                // Se existe, incrementar contador
                $finalName = $requestedName;

                if ($existingFolder) {
                    $counter = $this->extractCounter($existingFolder->name, $requestedName) ?? 1;
                    $finalName = "{$requestedName} ({$counter})";
                }

                // Garantir que o nome final é único
                while (
                    DriveFolder::where('name', $finalName)
                        ->where('parent_id', $parentId)
                        ->exists()
                ) {
                    $counter = ($this->extractCounter($finalName, $requestedName) ?? 0) + 1;
                    $finalName = "{$requestedName} ({$counter})";
                }

                $folder = DriveFolder::create([
                    'name' => $finalName,
                    'parent_id' => $parentId,
                ]);

                // Criar diretório no storage
                $folderPath = 'drive/'.$folder->getPath();

                if (! $disk->exists($folderPath)) {
                    $disk->makeDirectory($folderPath);
                }

                // drive_folder_id é preenchido automaticamente pela relação drives()
                $folder->drives()->create([
                    'user_id' => Auth::id(),
                    'name' => $folder->name,
                    'document_path' => $folderPath,
                    'document_size' => 0,
                    'document_type' => DocumentTypeDriveEnum::FOLDER,
                    'modified_by' => Auth::id(),
                ]);

                return $folder;
            });
        });
    }

    /**
     * Extrai o contador de um nome formatado
     * Ex: "Pasta 01 (2)" retorna 2
     */
    private function extractCounter(string $fullName, string $baseName): ?int
    {
        $pattern = preg_quote($baseName).'\s*\((\d+)\)$';

        if (preg_match("/{$pattern}/", $fullName, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function delete(string $id, Tenant $tenant)
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {

                $loggedInUser = Auth::user()->id;

                $folder = DriveFolder::findOrFail($id);

                $folder->drives()->update([
                    'modified_by' => $loggedInUser,
                    'modified_at' => now(),
                ]);

                $folder->drives()->delete();
                $folder->delete();

                return $folder;
            });
        });
    }

    public function findAll(Tenant $tenant)
    {
        return $tenant->run(function () {

            $folders = collect();

            DriveFolder::chunk(500, function ($chunk) use (&$folders) {
                $folders = $folders->merge($chunk);
            });

            return $folders;
        });
    }
}
