<?php

namespace App\Services;

use App\Enums\PropertyCondition;
use App\Enums\ProposalStatus;
use App\Mail\ApplicantWelcomeMail;
use App\Mail\ProposalCreatedMail;
use App\Models\Applicant;
use App\Models\ContractType;
use App\Models\Proposal;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class ProposalService
{
    /**
     * Campos da proposta aceitos do formulário. `status`, `finished_at` e
     * `particularities` têm fluxo próprio e nunca vêm daqui.
     *
     * @var array<int, string>
     */
    private const PROPOSAL_FIELDS = [
        'creator_id',
        'analyst_id',
        'bank_id',
        'contract_type_id',
        'amortization_table',
        'property_condition',
        'has_other_property',
        'purchase_value',
        'down_payment_value',
        'financing_value',
        'expenses_value',
        'subsidy_value',
        'financed_value',
        'intended_installment_value',
        'fgts_value',
        'uses_fgts',
        'is_first_financing',
        'finance_documentation_fee',
        'documentation_fee_to_finance',
        'payment_term',
        'declares_income_tax',
        'declared_income',
        'contract_notes',
        'purchase_value_notes',
        'down_payment_notes',
        'fgts_notes',
        'documentation_fee_notes',
        'documentation_financing_notes',
        'income_tax_notes',
        'general_notes',
    ];

    public function __construct(
        protected ApplicantService $applicantService,
        protected SellerService $sellerService,
        protected ProposalTimelineService $proposalTimelineService,
    ) {}

    /**
     * Cria a proposta com proponentes, vendedores, parceiros, imóvel e
     * timeline numa única transação. Os e-mails vão para a fila e só são
     * despachados após o commit.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, Tenant $tenant): Proposal
    {
        return $tenant->run(function () use ($data, $actor, $tenant) {
            return DB::transaction(function () use ($data, $actor, $tenant) {
                $proposal = Proposal::create(array_merge(
                    Arr::only($data, self::PROPOSAL_FIELDS),
                    [
                        'status' => ProposalStatus::NEW,
                        'analyst_id' => $data['analyst_id'] ?? $data['creator_id'],
                    ],
                ));

                foreach ($data['applicants'] as $applicantData) {
                    $applicant = $this->applicantService->findOrCreateByCpf($applicantData, $tenant);
                    $proposal->applicants()->syncWithoutDetaching([$applicant->id]);
                    $this->notifyApplicant($applicant, $proposal);
                }

                foreach ($data['sellers'] ?? [] as $sellerData) {
                    $seller = $this->sellerService->findOrCreateByDocument($sellerData, $actor, $tenant);
                    $proposal->sellers()->syncWithoutDetaching([$seller->id]);
                }

                $proposal->partners()->sync($data['partner_ids'] ?? []);

                if (! empty($data['property'])) {
                    $proposal->property()->create($data['property']);
                }

                $this->proposalTimelineService->instantiate($proposal, $tenant);

                return $proposal;
            });
        });
    }

    /**
     * Atualiza os dados da proposta, o status manual, os parceiros e o imóvel.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, string $id, Tenant $tenant): Proposal
    {
        return $tenant->run(function () use ($data, $id) {
            return DB::transaction(function () use ($data, $id) {
                $proposal = Proposal::lockForUpdate()->findOrFail($id);

                $proposal->fill(Arr::only($data, self::PROPOSAL_FIELDS));
                $proposal->analyst_id = $data['analyst_id'] ?? $proposal->creator_id;
                $this->applyStatus($proposal, $data);
                $proposal->save();

                $proposal->partners()->sync($data['partner_ids'] ?? []);

                if (empty($data['property'])) {
                    $proposal->property()->delete();
                } else {
                    $proposal->property()->updateOrCreate([], $data['property']);
                }

                return $proposal;
            });
        });
    }

    /**
     * Cria a proposta de um orçamento com os valores padrão: banco do
     * orçamento, status "Nova", criador e analista = quem converteu, valores
     * zerados, imóvel usado e o contrato padrão configurado. Anexa o
     * proponente (reaproveitado pelo CPF) e instancia a timeline.
     *
     * Não gera linhas de custo nem envia e-mail: isso é feito pela conversão,
     * dentro de cuja transação este método roda.
     */
    public function createFromQuote(Quote $quote, User $actor, Tenant $tenant): Proposal
    {
        return $tenant->run(function () use ($quote, $actor, $tenant) {
            $proposal = Proposal::create([
                'creator_id' => $actor->id,
                'analyst_id' => $actor->id,
                'bank_id' => $quote->bank_id,
                'contract_type_id' => $this->defaultContractTypeId(),
                'status' => ProposalStatus::NEW,
                'property_condition' => PropertyCondition::USED,
                'purchase_value' => 0,
                'down_payment_value' => 0,
            ]);

            $applicant = $this->applicantService->findOrCreateByCpf([
                'cpf' => $quote->cpf,
                'name' => $quote->name,
                'email' => $quote->email,
                'phone' => $quote->phone,
                'profession' => $quote->profession,
                'marital_status' => $quote->marital_status,
            ], $tenant);

            $proposal->applicants()->attach($applicant->id);
            $proposal->setRelation('applicants', collect([$applicant]));

            $this->proposalTimelineService->instantiate($proposal, $tenant);

            return $proposal;
        });
    }

    public function updateParticularities(?string $particularities, string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => Proposal::findOrFail($id)->update(['particularities' => $particularities]));
    }

    /**
     * Soft delete da proposta. Os usuários dos proponentes são preservados: o
     * mesmo proponente pode estar em outras propostas.
     */
    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => (bool) Proposal::findOrFail($id)->delete());
    }

    public function findById(string $id, Tenant $tenant): Proposal
    {
        return $tenant->run(fn () => Proposal::findOrFail($id));
    }

    private function defaultContractTypeId(): int
    {
        $configured = ContractType::whereKey(config('proposals.default_contract_type_id'))->value('id');

        return $configured ?? ContractType::ordered()->value('id');
    }

    /**
     * Aplica o status escolhido no formulário, limpando os motivos que não se
     * aplicam ao novo status. "Finalizada" só é atingido pela timeline e,
     * uma vez finalizada, a proposta mantém o status e a data de finalização.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyStatus(Proposal $proposal, array $data): void
    {
        if (! isset($data['status']) || $proposal->status === ProposalStatus::FINISHED) {
            return;
        }

        $status = ProposalStatus::from($data['status']);

        $proposal->status = $status;
        $proposal->cancellation_reason = $status === ProposalStatus::CANCELED ? $data['cancellation_reason'] : null;
        $proposal->restriction_reason = $status === ProposalStatus::RESTRICTED ? $data['restriction_reason'] : null;
        $proposal->expected_delivery_month = $status === ProposalStatus::AWAITING_PROPERTY ? $data['expected_delivery_month'] : null;
        $proposal->expected_delivery_year = $status === ProposalStatus::AWAITING_PROPERTY ? $data['expected_delivery_year'] : null;
    }

    /**
     * Usuário criado agora recebe o link de definição de senha; proponente já
     * existente recebe o aviso de nova proposta. O link é montado aqui (na
     * requisição) porque a fila não conhece o domínio do tenant.
     */
    public function notifyApplicant(Applicant $applicant, Proposal $proposal): void
    {
        $user = $applicant->user;
        $email = $user?->email ?? $applicant->contact->email;
        $name = $applicant->contact->name_corporatereason;

        if ($user !== null && $user->wasRecentlyCreated) {
            $url = route('tenant.reset-password', [
                'token' => Password::broker()->createToken($user),
                'email' => $user->email,
            ]);

            Mail::to($email)->queue(new ApplicantWelcomeMail($name, $proposal->number, $url));

            return;
        }

        Mail::to($email)->queue(new ProposalCreatedMail($name, $proposal->number));
    }
}
