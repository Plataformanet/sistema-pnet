<?php

namespace App\Services;

use App\Enums\PersonType;
use App\Enums\RolesEnum;
use App\Models\Applicant;
use App\Models\Contact;
use App\Models\Proposal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ApplicantService
{
    public function __construct(
        protected ContactService $contactService,
    ) {}

    /**
     * Reaproveita o proponente pelo CPF ou cria contato, usuário (cargo Cliente,
     * sem senha utilizável) e proponente. Um proponente existente é só
     * devolvido: seus dados e sua conta bancária não são sobrescritos.
     *
     * É o mesmo ponto de entrada da criação manual da proposta e da conversão
     * de orçamento. O usuário fica carregado em `$applicant->user`, com
     * `wasRecentlyCreated` indicando se o e-mail de boas-vindas deve ser enviado.
     *
     * @param  array<string, mixed>  $data
     */
    public function findOrCreateByCpf(array $data, Tenant $tenant): Applicant
    {
        return $tenant->run(function () use ($data, $tenant) {
            $cpf = preg_replace('/\D/', '', (string) $data['cpf']);
            $contact = $this->contactService->getContactByCpfCnpj($cpf, $tenant);

            $applicant = $contact?->applicant()->withTrashed()->first();

            if ($applicant !== null) {
                if ($applicant->trashed()) {
                    $applicant->restore();
                }

                if ($applicant->user_id === null) {
                    $applicant->user()->associate($this->resolveUser($contact, (string) ($data['email'] ?? $contact->email)));
                    $applicant->save();
                }

                return $applicant->load('user');
            }

            $contact ??= Contact::create([
                'type' => PersonType::INDIVIDUAL->value,
                'name_corporatereason' => $data['name'],
                'cpf_cnpj' => $cpf,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'cell_phone' => $data['phone'],
            ]);

            $user = $this->resolveUser($contact, (string) $data['email']);

            $applicant = $contact->applicant()->create(array_merge(
                Arr::only($data, [
                    'birth_date',
                    'marital_status',
                    'profession',
                    'family_income',
                    'declared_income',
                    'declares_income_tax',
                    'income_tax_notes',
                    'by_power_of_attorney',
                ]),
                ['user_id' => $user->id],
            ));

            if (! empty($data['bank_account'])) {
                $applicant->bankAccount()->create($data['bank_account']);
            }

            return $applicant->setRelation('user', $user);
        });
    }

    /**
     * Dados do proponente para pré-preencher o formulário da proposta. Quem só
     * tem cargos externos (ex.: Parceiro) recebe apenas o nome e se o
     * proponente já existe: o CPF pode ser de qualquer contato do tenant
     * (cliente, fornecedor, funcionário), e contato, renda e conta bancária
     * dele não podem vazar para fora da equipe.
     *
     * @return array<string, mixed>|null
     */
    public function lookup(string $cpf, User $viewer, Tenant $tenant): ?array
    {
        return $tenant->run(function () use ($cpf, $viewer, $tenant) {
            $contact = $this->contactService->getContactByCpfCnpj(preg_replace('/\D/', '', $cpf), $tenant);

            if ($contact === null) {
                return null;
            }

            $applicant = $contact->applicant()->with('bankAccount')->first();

            if ($viewer->hasOnlyProposalRestrictedRoles()) {
                return [
                    'existing' => $applicant !== null,
                    'name' => $contact->name_corporatereason,
                    'cpf' => preg_replace('/\D/', '', $contact->cpf_cnpj),
                ];
            }

            return [
                'existing' => $applicant !== null,
                'name' => $contact->name_corporatereason,
                'cpf' => preg_replace('/\D/', '', $contact->cpf_cnpj),
                'email' => $contact->email,
                'phone' => $contact->cell_phone ?: $contact->phone,
                'birth_date' => $applicant?->birth_date?->format('Y-m-d'),
                'marital_status' => $applicant?->marital_status?->value,
                'profession' => $applicant?->profession,
                'family_income' => $applicant?->family_income,
                'declared_income' => $applicant?->declared_income,
                'declares_income_tax' => (bool) $applicant?->declares_income_tax,
                'income_tax_notes' => $applicant?->income_tax_notes,
                'by_power_of_attorney' => (bool) $applicant?->by_power_of_attorney,
                'bank_account' => $applicant?->bankAccount?->only(['bank_name', 'account_type', 'branch', 'number', 'notes']),
            ];
        });
    }

    /**
     * Proponentes da proposta com os dados editáveis na tela de edição
     * (contato, renda e conta bancária), já no formato do formulário.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForProposal(string $proposalId, Tenant $tenant): array
    {
        return $tenant->run(fn () => Proposal::findOrFail($proposalId)->applicants()
            ->with(['contact:id,name_corporatereason,cpf_cnpj,email,phone,cell_phone', 'bankAccount'])
            ->orderBy('applicant_proposal.id')
            ->get()
            ->map(fn (Applicant $applicant) => [
                'id' => $applicant->id,
                'cpf' => preg_replace('/\D/', '', $applicant->contact->cpf_cnpj),
                'name' => $applicant->contact->name_corporatereason,
                'email' => $applicant->contact->email,
                'phone' => $applicant->contact->cell_phone ?: $applicant->contact->phone,
                'birth_date' => $applicant->birth_date?->format('Y-m-d'),
                'marital_status' => $applicant->marital_status?->value,
                'profession' => $applicant->profession,
                'family_income' => $applicant->family_income,
                'declared_income' => $applicant->declared_income,
                'declares_income_tax' => $applicant->declares_income_tax,
                'income_tax_notes' => $applicant->income_tax_notes,
                'by_power_of_attorney' => $applicant->by_power_of_attorney,
                'bank_account' => $applicant->bankAccount?->only(['bank_name', 'account_type', 'branch', 'number', 'notes']),
            ])
            ->all());
    }

    /**
     * Atualiza um proponente da proposta. Nome, e-mail e telefone ficam no
     * contato, compartilhado com os demais papéis da mesma pessoa. O CPF não
     * muda: é a chave que reaproveita o proponente entre propostas. Conta
     * bancária sem dados é removida.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, string $proposalId, string $applicantId, Tenant $tenant): Applicant
    {
        return $tenant->run(fn () => DB::transaction(function () use ($data, $proposalId, $applicantId) {
            $applicant = Proposal::findOrFail($proposalId)->applicants()->with('contact')->findOrFail($applicantId);

            $applicant->contact->update([
                'name_corporatereason' => $data['name'],
                'email' => $data['email'],
                'cell_phone' => $data['phone'],
            ]);

            $applicant->update(Arr::only($data, [
                'birth_date',
                'marital_status',
                'profession',
                'family_income',
                'declared_income',
                'declares_income_tax',
                'income_tax_notes',
                'by_power_of_attorney',
            ]));

            if (empty($data['bank_account'])) {
                $applicant->bankAccount()->delete();
            } else {
                $applicant->bankAccount()->updateOrCreate([], $data['bank_account']);
            }

            return $applicant;
        }));
    }

    /**
     * Usuário de acesso do proponente: reaproveita o do e-mail ou cria um novo
     * com senha aleatória descartada (o acesso é liberado pelo link de
     * definição de senha, nunca por senha enviada por e-mail).
     */
    private function resolveUser(Contact $contact, string $email): User
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $user = User::create([
                'name' => $contact->name_corporatereason,
                'email' => $email,
                'password' => Str::password(32),
            ]);
        }

        if ($user->roles()->doesntExist()) {
            $role = Role::firstOrCreate(['name' => RolesEnum::CLIENT->label(), 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return $user;
    }
}
