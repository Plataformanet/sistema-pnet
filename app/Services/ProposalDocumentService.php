<?php

namespace App\Services;

use App\Enums\DocumentOwner;
use App\Enums\DocumentType;
use App\Enums\PersonType;
use App\Models\Applicant;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\Seller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Documentos da proposta (RG, IRPF, CTPS…): dados pessoais, por isso sempre no
 * disco privado do bucket, sob o prefixo do tenant, e baixados só por rota
 * autorizada.
 */
class ProposalDocumentService
{
    public const BASE_PATH = 'proposals';

    public function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(config('bucket.disk'));

        return $disk;
    }

    public static function basePath(int $proposalId): string
    {
        return self::BASE_PATH.'/'.$proposalId;
    }

    /**
     * Envia um ou mais documentos para a proposta. Para comprador/vendedor,
     * `person_id` identifica o proponente/vendedor dono do documento, que
     * precisa estar vinculado à proposta.
     *
     * @param  array{owner: string, type?: string|null, title?: string|null, person_id?: int|string|null}  $data
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, ProposalDocument>
     */
    public function store(string $proposalId, array $data, array $files, User $uploader, Tenant $tenant): Collection
    {
        return $tenant->run(function () use ($proposalId, $data, $files, $uploader) {
            $proposal = Proposal::findOrFail($proposalId);
            $owner = DocumentOwner::from($data['owner']);
            $documentable = $this->resolveDocumentable($proposal, $owner, $data['person_id'] ?? null);
            $type = isset($data['type']) ? DocumentType::from($data['type']) : null;

            return collect($files)->map(fn (UploadedFile $file) => $this->storeFile($proposal, $file, [
                'uploaded_by' => $uploader->id,
                'owner' => $owner,
                'documentable_type' => $documentable?->getMorphClass(),
                'documentable_id' => $documentable?->getKey(),
                'type' => $type,
                'title' => $data['title'] ?? $type?->label() ?? $file->getClientOriginalName(),
            ]));
        });
    }

    /**
     * Grava o arquivo no disco e cria o registro. Se a gravação do registro
     * falhar, o arquivo já enviado é removido para não deixar órfãos.
     *
     * Deve ser chamado dentro do contexto do tenant.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function storeFile(Proposal $proposal, UploadedFile $file, array $attributes): ProposalDocument
    {
        $path = $this->disk()->putFileAs(
            self::basePath($proposal->id),
            $file,
            Str::uuid().'.'.($file->getClientOriginalExtension() ?: $file->extension()),
        );

        try {
            return $proposal->documents()->create(array_merge($attributes, [
                'disk' => config('bucket.disk'),
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]));
        } catch (Throwable $exception) {
            $this->disk()->delete($path);

            throw $exception;
        }
    }

    /**
     * Anexa à proposta um documento gerado pelo sistema (ex.: PDF do orçamento
     * na conversão em proposta), com dono "Geral".
     */
    public function storeGenerated(Proposal $proposal, string $contents, DocumentType $type, string $title, Tenant $tenant): ProposalDocument
    {
        return $tenant->run(function () use ($proposal, $contents, $type, $title) {
            $path = self::basePath($proposal->id).'/'.Str::uuid().'.pdf';

            $this->disk()->put($path, $contents);

            try {
                return $proposal->documents()->create([
                    'owner' => DocumentOwner::GENERAL,
                    'type' => $type,
                    'title' => $title,
                    'disk' => config('bucket.disk'),
                    'path' => $path,
                    'original_name' => Str::slug($title).'.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => strlen($contents),
                ]);
            } catch (Throwable $exception) {
                $this->disk()->delete($path);

                throw $exception;
            }
        });
    }

    public function findById(string $proposalId, string $documentId, Tenant $tenant): ProposalDocument
    {
        return $tenant->run(fn () => ProposalDocument::where('proposal_id', $proposalId)->findOrFail($documentId));
    }

    public function download(ProposalDocument $document, Tenant $tenant): StreamedResponse
    {
        return $tenant->run(fn () => Storage::disk($document->disk)->download($document->path, $document->original_name));
    }

    /**
     * O registro sai na transação; o arquivo só é apagado depois do commit,
     * por ser irreversível.
     */
    public function delete(string $proposalId, string $documentId, Tenant $tenant): void
    {
        $tenant->run(function () use ($proposalId, $documentId) {
            $document = DB::transaction(function () use ($proposalId, $documentId) {
                $document = ProposalDocument::where('proposal_id', $proposalId)->findOrFail($documentId);
                $document->delete();

                return $document;
            });

            Storage::disk($document->disk)->delete($document->path);
        });
    }

    /**
     * Checklist de documentos obrigatórios por pessoa e do imóvel: tipos
     * exigidos x tipos já enviados.
     *
     * @return array<int, array{owner: string, person_id: int|null, name: string, required: array<int, array{value: string, label: string, sent: bool}>, complete: bool}>
     */
    public function checklist(Proposal $proposal): array
    {
        $proposal->loadMissing(['applicants.contact', 'sellers.contact', 'documents']);

        $sentBy = fn (DocumentOwner $owner, ?string $type = null, ?int $id = null) => $proposal->documents
            ->filter(fn (ProposalDocument $document) => $document->owner === $owner
                && ($type === null || ($document->documentable_type === $type && $document->documentable_id === $id)))
            ->map(fn (ProposalDocument $document) => $document->type)
            ->filter()
            ->unique();

        $build = function (DocumentOwner $owner, string $name, ?int $personId, Collection $sent, ?PersonType $personType = null) {
            $required = collect(DocumentType::requiredFor($owner, $personType))
                ->map(fn (DocumentType $type) => ['value' => $type->value, 'label' => $type->label(), 'sent' => $sent->contains($type)])
                ->values()
                ->all();

            return [
                'owner' => $owner->value,
                'person_id' => $personId,
                'name' => $name,
                'required' => $required,
                'complete' => collect($required)->every(fn (array $item) => $item['sent']),
            ];
        };

        $applicantClass = (new Applicant)->getMorphClass();
        $sellerClass = (new Seller)->getMorphClass();

        return array_merge(
            $proposal->applicants->map(fn (Applicant $applicant) => $build(
                DocumentOwner::BUYER,
                $applicant->contact->name_corporatereason,
                $applicant->id,
                $sentBy(DocumentOwner::BUYER, $applicantClass, $applicant->id),
            ))->all(),
            $proposal->sellers->map(fn (Seller $seller) => $build(
                DocumentOwner::SELLER,
                $seller->contact->name_corporatereason,
                $seller->id,
                $sentBy(DocumentOwner::SELLER, $sellerClass, $seller->id),
                $seller->personType(),
            ))->all(),
            [$build(DocumentOwner::PROPERTY, 'Imóvel', null, $sentBy(DocumentOwner::PROPERTY))],
        );
    }

    /**
     * Documentos obrigatórios do vendedor ainda não enviados, por vendedor.
     * Comparação por conjunto (o legado comparava por posição e errava).
     *
     * @return array<string, array<int, string>>
     */
    public function missingSellerDocuments(Proposal $proposal): array
    {
        return collect($this->checklist($proposal))
            ->filter(fn (array $item) => $item['owner'] === DocumentOwner::SELLER->value && ! $item['complete'])
            ->mapWithKeys(fn (array $item) => [
                $item['name'] => collect($item['required'])->reject(fn (array $type) => $type['sent'])->pluck('label')->all(),
            ])
            ->all();
    }

    private function resolveDocumentable(Proposal $proposal, DocumentOwner $owner, int|string|null $personId): Applicant|Seller|null
    {
        $relation = match ($owner) {
            DocumentOwner::BUYER => $proposal->applicants(),
            DocumentOwner::SELLER => $proposal->sellers(),
            default => null,
        };

        if ($relation === null) {
            return null;
        }

        $person = $personId !== null ? $relation->find($personId) : null;

        if ($person === null) {
            throw ValidationException::withMessages([
                'person_id' => 'Selecione a pessoa da proposta dona do documento.',
            ]);
        }

        return $person;
    }
}
