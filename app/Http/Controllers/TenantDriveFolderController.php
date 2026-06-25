<?php

namespace App\Http\Controllers;

use App\Enums\DocumentTypeDriveEnum;
use App\Http\Requests\StoreDriveFolderRequest;
use App\Models\DriveFolder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class TenantDriveFolderController extends Controller
{
    public function store(StoreDriveFolderRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $disk          = Storage::disk('public');
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
                $count     = $this->extractCounter($existingFolder->name, $requestedName) ?? 1;
                $finalName = "{$requestedName} ({$count})";
            }

            // Garantir que o nome final é único
            while (
                DriveFolder::where('name', $finalName)
                    ->where('parent_id', $parentId)
                    ->exists()
            ) {
                $count     = ($this->extractCounter($finalName, $requestedName) ?? 0) + 1;
                $finalName = "{$requestedName} ({$count})";
            }

            $folder = DriveFolder::create([
                'name'      => $finalName,
                'parent_id' => $parentId,
            ]);

            // Criar diretório no storage
            $folderPath = 'drive/' . $folder->getPath();

            if (!$disk->exists($folderPath)) {
                Storage::disk('public')->createDirectory(
                    $folderPath,
                    [
                        'visibility'           => 'public',
                        'directory_visibility' => 'public',
                    ]
                );
            }

            $folder->drives()->create([
                'user_id'           => Auth::id(),
                'drive_pasta_id'    => $folder->id,
                'name'              => $folder->nome,
                'documento_path'    => $folderPath,
                'tamanho_documento' => null,
                'tipo_documento'    => DocumentTypeDriveEnum::FOLDER,
                'modified_by'       => Auth::id(),
            ]);

            return $folder;
        });
    }

    /**
     * Extrai o contador de um nome formatado
     * Ex: "Pasta 01 (2)" retorna 2
     */
    private function extractCounter(string $fullName, string $nomeBase): ?int
    {
        $pattern = preg_quote($nomeBase) . '\s*\((\d+)\)$';

        if (preg_match("/{$pattern}/", $fullName, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function delete(string $id)
    {
        $loggedInUser = auth()->user()->id;

        $driveFolder = DriveFolder::findOrFail($id);

        $driveFolder->drives()->update([
            'modified_by' => $loggedInUser,
            'modified_at' => now()
        ]);

        $driveFolder->drives()->delete();
        $driveFolder->delete();

        return $driveFolder;
    }

    public function findAll()
    {
        $folders = collect();

        DriveFolder::chunk(500, function ($chunk) use (&$folders) {
            $folders = $folders->merge($chunk);
        });

        return $folders;
    }

}
