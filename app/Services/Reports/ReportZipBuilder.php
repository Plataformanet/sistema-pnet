<?php

namespace App\Services\Reports;

use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Monta o ZIP com o documento da etapa final de cada proposta do relatório,
 * lendo do disco privado de cada documento. Deve rodar dentro do contexto do
 * tenant (os discos são prefixados por tenant).
 */
class ReportZipBuilder
{
    public const TMP_PATH = 'app/tmp';

    /**
     * Cada documento ainda passa pela `ProposalPolicy::downloadDocument` do
     * usuário (defesa em profundidade). Arquivo que sumiu do disco não aborta
     * o lote: vai para um LEIA-ME.txt dentro do ZIP e gera log de aviso.
     *
     * @param  Collection<int, Proposal>  $proposals  com `finalStage.document` carregado
     * @return string caminho absoluto do ZIP temporário
     */
    public function build(Collection $proposals, User $user): string
    {
        $directory = storage_path(self::TMP_PATH);
        File::ensureDirectoryExists($directory);

        $zipPath = $directory.'/'.Str::uuid()->toString().'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o arquivo ZIP do relatório.');
        }

        $temporaryFiles = [];
        $usedNames = [];
        $missing = [];

        try {
            foreach ($proposals as $proposal) {
                $document = $proposal->finalStage?->document;

                if ($document === null || Gate::forUser($user)->denies('downloadDocument', [$proposal, $document])) {
                    continue;
                }

                $disk = Storage::disk($document->disk);

                if (! $disk->exists($document->path)) {
                    $missing[] = "Proposta Nº {$proposal->number}: {$document->title}";
                    Log::warning('Documento final ausente no disco ao gerar o ZIP do relatório.', [
                        'proposal_id' => $proposal->id,
                        'document_id' => $document->id,
                    ]);

                    continue;
                }

                $temporaryFile = $this->copyToTemporaryFile($disk->readStream($document->path), $directory);
                $temporaryFiles[] = $temporaryFile;

                $zip->addFile($temporaryFile, $this->entryName($proposal, $document, $usedNames));
            }

            if ($missing !== []) {
                $zip->addFromString('LEIA-ME.txt', "Os arquivos abaixo não foram encontrados no armazenamento e ficaram fora deste ZIP:\n\n- ".implode("\n- ", $missing)."\n");
            }

            if ($zip->numFiles === 0) {
                $zip->addFromString('LEIA-ME.txt', "Nenhum documento disponível para download.\n");
            }

            $zip->close();
        } finally {
            File::delete($temporaryFiles);
        }

        return $zipPath;
    }

    /**
     * `proposta-{nº com 5 dígitos}-{slug do título}.{extensão}`, com sufixo
     * numérico se o nome se repetir.
     *
     * @param  array<string, true>  $usedNames
     */
    public function entryName(Proposal $proposal, ProposalDocument $document, array &$usedNames = []): string
    {
        $extension = strtolower(pathinfo((string) $document->original_name, PATHINFO_EXTENSION));
        $slug = Str::slug((string) $document->title) ?: 'documento';
        $base = "proposta-{$proposal->number}-{$slug}";
        $suffix = $extension !== '' ? '.'.$extension : '';

        $name = $base.$suffix;

        for ($counter = 2; isset($usedNames[$name]); $counter++) {
            $name = "{$base}-{$counter}{$suffix}";
        }

        $usedNames[$name] = true;

        return $name;
    }

    /**
     * @param  resource|null  $stream
     */
    private function copyToTemporaryFile($stream, string $directory): string
    {
        if (! is_resource($stream)) {
            throw new RuntimeException('Não foi possível ler um documento do relatório.');
        }

        $path = $directory.'/'.Str::uuid()->toString();
        $target = fopen($path, 'wb');

        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            fclose($target);
            fclose($stream);
        }

        return $path;
    }
}
