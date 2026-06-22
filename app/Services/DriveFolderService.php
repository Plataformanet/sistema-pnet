<?php

namespace App\Services;

use App\Enums\DocumentTypeDriveEnum;
use App\Http\Requests\StoreDriveFolderRequest;
use App\Models\DriveFolder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class DriveFolderService
{
    public function store(StoreDriveFolderRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $disk           = Storage::disk('public');
            $nomeSolicitado = $request->validated('nome');

            // Definir parent_id se houver (pasta dentro de pasta)
            $parentId = $request->validated('parent_id', null);

            // Buscar apenas pastas com o mesmo nome e parent no banco
            $pastaExistente = DriveFolder::where('nome', $nomeSolicitado)
                ->where('parent_id', $parentId)
                ->latest('id')
                ->first();

            // Se existe, incrementar contador
            $nomeDefinitivo = $nomeSolicitado;

            if ($pastaExistente) {
                $contador       = $this->extrairContador($pastaExistente->nome, $nomeSolicitado) ?? 1;
                $nomeDefinitivo = "{$nomeSolicitado} ({$contador})";
            }

            // Garantir que o nome final é único
            while (
                DriveFolder::where('nome', $nomeDefinitivo)
                    ->where('parent_id', $parentId)
                    ->exists()
            ) {
                $contador       = ($this->extrairContador($nomeDefinitivo, $nomeSolicitado) ?? 0) + 1;
                $nomeDefinitivo = "{$nomeSolicitado} ({$contador})";
            }

            $pasta = DriveFolder::create([
                'nome'      => $nomeDefinitivo,
                'parent_id' => $parentId,
            ]);

            // Criar diretório no storage
            $caminhoPasta = 'drive/' . $pasta->getPath();

            if (!$disk->exists($caminhoPasta)) {
                Storage::disk('public')->createDirectory(
                    $caminhoPasta,
                    [
                        'visibility'           => 'public',
                        'directory_visibility' => 'public',
                    ]
                );
            }

            $pasta->drives()->create([
                'user_id'           => Auth::id(),
                'drive_pasta_id'    => $pasta->id,
                'nome'              => $pasta->nome,
                'documento_path'    => $caminhoPasta,
                'tamanho_documento' => null,
                'tipo_documento'    => DocumentTypeDriveEnum::FOLDER,
                'modified_by'       => Auth::id(),
            ]);

            return $pasta;
        });
    }

    /**
     * Extrai o contador de um nome formatado
     * Ex: "Pasta 01 (2)" retorna 2
     */
    private function extrairContador(string $nomeCompleto, string $nomeBase): ?int
    {
        $padrao = preg_quote($nomeBase) . '\s*\((\d+)\)$';

        if (preg_match("/{$padrao}/", $nomeCompleto, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function delete(string $id)
    {
        $usuarioLogado = auth()->user()->id;

        $drivePasta = DriveFolder::findOrFail($id);

        $drivePasta->drives()->update([
            'modified_by' => $usuarioLogado,
            'modified_at' => now()
        ]);

        $drivePasta->drives()->delete();
        $drivePasta->delete();

        return $drivePasta;
    }

    public function findAll()
    {

        $pastas = collect();

        DriveFolder::chunk(500, function ($chunk) use (&$pastas) {
            $pastas = $pastas->merge($chunk);
        });

        return $pastas;
    }
}
