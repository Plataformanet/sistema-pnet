<?php

namespace App\Services;

use App\Enums\AmortizationTableEnum;
use App\Enums\BankAccountTypeEnum;
use App\Enums\DocumentOwnerEnum;
use App\Enums\DocumentTypeEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\PersonTypeEnum;
use App\Enums\PropertyConditionEnum;
use App\Enums\ProposalStatusEnum;
use App\Enums\RolesEnum;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Leituras de propostas: listagem, contadores e telas. Toda consulta passa
 * por `Proposal::visibleTo` para respeitar a visibilidade de cada cargo.
 */
class ProposalQueryService
{
    public const PER_PAGE = 20;

    /**
     * Máximo de linhas do PDF de lista: o dompdf monta tudo em memória.
     */
    public const PRINT_LIMIT = 500;

    public function __construct(
        protected ProposalDocumentService $proposalDocumentService,
        protected BankService $bankService,
        protected ContractTypeService $contractTypeService,
        protected PropertyTypeService $propertyTypeService,
        protected DevelopmentService $developmentService,
        protected CostTypeService $costTypeService,
        protected NotaryService $notaryService,
    ) {}

    /**
     * Opções dos selects do formulário. Cadastros excluídos não são
     * oferecidos, exceto o valor já gravado na proposta em edição.
     *
     * @return array<string, mixed>
     */
    public function formOptions(Tenant $tenant, ?Proposal $proposal = null): array
    {
        $staffRoles = RolesEnum::proposalStaffLabels();

        return [
            'banks' => $this->bankService->options($tenant, $proposal?->bank_id)->map->only(['id', 'name']),
            'contractTypes' => $this->contractTypeService->options($tenant, $proposal?->contract_type_id)->map->only(['id', 'name', 'requires_financing']),
            'propertyTypes' => $this->propertyTypeService->options($tenant, $proposal?->property?->property_type_id)
                ->map->only(['id', 'name', 'shows_number', 'shows_complement', 'requires_development', 'shows_unit', 'shows_block']),
            'developments' => $this->developmentService->options($tenant, $proposal?->property?->development_id)->map->only(['id', 'name']),
            'staff' => $tenant->run(fn () => $this->usersWithRoles($staffRoles)),
            'partners' => $tenant->run(fn () => $this->usersWithRoles([RolesEnum::PARTNER->label()])),
            'maritalStatuses' => MaritalStatusEnum::options(),
            'amortizationTables' => AmortizationTableEnum::options(),
            'propertyConditions' => PropertyConditionEnum::options(),
            'personTypes' => PersonTypeEnum::options(),
            'bankAccountTypes' => BankAccountTypeEnum::options(),
        ];
    }

    /**
     * Opções das abas de documentos e pagamentos da edição.
     *
     * @return array<string, mixed>
     */
    public function managementOptions(Tenant $tenant): array
    {
        return [
            'costTypes' => $this->costTypeService->options($tenant)->map->only(['id', 'name', 'requires_notary', 'receipt_type']),
            'notaries' => $this->notaryService->options($tenant)->map->only(['id', 'name']),
            'statuses' => ProposalStatusEnum::options(ProposalStatusEnum::manuallySelectable()),
            'documentTypes' => [
                DocumentOwnerEnum::BUYER->value => DocumentTypeEnum::options(DocumentTypeEnum::forOwner(DocumentOwnerEnum::BUYER)),
                'seller_pf' => DocumentTypeEnum::options(DocumentTypeEnum::forOwner(DocumentOwnerEnum::SELLER, PersonTypeEnum::INDIVIDUAL)),
                'seller_pj' => DocumentTypeEnum::options(DocumentTypeEnum::forOwner(DocumentOwnerEnum::SELLER, PersonTypeEnum::COMPANY)),
                DocumentOwnerEnum::PROPERTY->value => DocumentTypeEnum::options(DocumentTypeEnum::forOwner(DocumentOwnerEnum::PROPERTY)),
                DocumentOwnerEnum::GENERAL->value => DocumentTypeEnum::options(DocumentTypeEnum::forOwner(DocumentOwnerEnum::GENERAL)),
            ],
            'maxUploadKb' => config('bucket.proposal_document_max_kb'),
        ];
    }

    /**
     * Documentos que o usuário pode ver na proposta, conforme o cargo (ver
     * `DocumentOwnerEnum::visibleTo`).
     *
     * @return Collection<int, ProposalDocument>
     */
    public function documentsFor(Proposal $proposal, User $user): Collection
    {
        $visibleOwners = DocumentOwnerEnum::visibleTo($user);

        return $proposal->documents
            ->filter(fn (ProposalDocument $document) => in_array($document->owner, $visibleOwners, true))
            ->values();
    }

    /**
     * @param  array{status?: string|null, search?: string|null}  $filters
     */
    public function paginate(array $filters, User $user, Tenant $tenant): LengthAwarePaginator
    {
        return $tenant->run(fn () => $this->filteredQuery($filters, $user)
            ->with([
                'applicants.contact:id,name_corporatereason,cpf_cnpj',
                'creator:id,name',
                'currentStage.stage:id,name',
            ])
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Proposal $proposal) => $this->listRow($proposal)));
    }

    /**
     * Linhas para os PDFs de lista (por status) e seleção (ids marcados).
     *
     * @param  array{status?: string|null, search?: string|null, ids?: array<int, int|string>|null}  $filters
     * @return Collection<int, Proposal>
     */
    public function forPrint(array $filters, User $user, Tenant $tenant): Collection
    {
        $filters = empty($filters['ids']) ? $filters : ['ids' => $filters['ids']];

        return $tenant->run(fn () => $this->filteredQuery($filters, $user)
            ->when($filters['ids'] ?? null, fn (Builder $query, array $ids) => $query->whereKey($ids))
            ->with([
                'applicants.contact:id,name_corporatereason,cpf_cnpj',
                'creator:id,name',
                'bank:id,name',
                'currentStage.stage:id,name',
            ])
            ->latest('id')
            ->limit(self::PRINT_LIMIT)
            ->get());
    }

    /**
     * Quantidade de propostas visíveis por status, numa única consulta.
     *
     * @return array<string, int>
     */
    public function statusCounts(User $user, Tenant $tenant): array
    {
        return $tenant->run(function () use ($user) {
            $counts = Proposal::query()
                ->visibleTo($user)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return collect(ProposalStatusEnum::cases())
                ->mapWithKeys(fn (ProposalStatusEnum $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
                ->all();
        });
    }

    /**
     * Proposta com tudo o que a tela de edição/visualização e os PDFs exibem.
     * Dos proponentes e vendedores só vêm os ids e o contato: conta bancária
     * e renda não são usadas nessas telas e não devem chegar a outra parte
     * (comprador, vendedor ou parceiro) pelas props da página.
     */
    public function findForDisplay(string $id, Tenant $tenant): Proposal
    {
        return $tenant->run(fn () => Proposal::with([
            'creator:id,name,email',
            'analyst:id,name',
            'bank:id,name,deleted_at',
            'contractType:id,name,requires_financing,deleted_at',
            'partners:id,name',
            'applicants:id,contact_id',
            'applicants.contact:id,name_corporatereason,cpf_cnpj,email,phone,cell_phone',
            'sellers:id,contact_id',
            'sellers.contact:id,type,name_corporatereason,cpf_cnpj,email,phone,cell_phone',
            'property.propertyType:id,name,deleted_at',
            'property.development:id,name,deleted_at',
            'stages.stage',
            'stages.document:id,title,original_name',
            'costItems' => fn ($query) => $query->orderBy('id'),
            'costItems.costType:id,name,receipt_type,requires_notary,deleted_at',
            'costItems.notary:id,name,deleted_at',
            'receipts' => fn ($query) => $query->latest('id'),
            'documents' => fn ($query) => $query->latest('id'),
            'documents.uploader:id,name',
        ])->findOrFail($id));
    }

    /**
     * Checklist de documentos obrigatórios da proposta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function checklist(Proposal $proposal, Tenant $tenant): array
    {
        return $tenant->run(fn () => $this->proposalDocumentService->checklist($proposal));
    }

    /**
     * Usuários com algum dos cargos informados (cargo inexistente no tenant
     * apenas não retorna ninguém).
     *
     * @param  array<int, string>  $roles
     * @return Collection<int, User>
     */
    private function usersWithRoles(array $roles): Collection
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', $roles))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  array{status?: string|null, search?: string|null}  $filters
     */
    private function filteredQuery(array $filters, User $user): Builder
    {
        $search = $filters['search'] ?? null;
        $digits = $search !== null ? preg_replace('/\D/', '', $search) : '';

        return Proposal::query()
            ->visibleTo($user)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search, $digits) {
                if ($digits !== '' && strlen($digits) <= 9) {
                    $query->orWhere('proposals.id', (int) $digits);
                }

                $query->orWhereHas('applicants.contact', function (Builder $query) use ($search, $digits) {
                    $query->where('name_corporatereason', 'like', '%'.$search.'%');

                    if ($digits !== '') {
                        $query->orWhere('cpf_cnpj', 'like', '%'.$digits.'%');
                    }
                });
            }));
    }

    /**
     * @return array<string, mixed>
     */
    private function listRow(Proposal $proposal): array
    {
        return [
            'id' => $proposal->id,
            'number' => $proposal->number,
            'created_at' => $proposal->created_at?->toIso8601String(),
            'applicants' => $proposal->applicants->map(fn ($applicant) => $applicant->contact->name_corporatereason)->values()->all(),
            'creator' => $proposal->creator?->name,
            'property_condition' => $proposal->property_condition->label(),
            'purchase_value' => $proposal->purchase_value,
            'status' => [
                'value' => $proposal->status->value,
                'label' => $proposal->status->label(),
                'color' => $proposal->status->color(),
            ],
            'current_stage' => $proposal->currentStage?->started_at !== null
                ? $proposal->currentStage->stage?->name
                : null,
        ];
    }
}
