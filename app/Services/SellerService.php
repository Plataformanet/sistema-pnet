<?php

namespace App\Services;

use App\Enums\PersonType;
use App\Models\Contact;
use App\Models\Proposal;
use App\Models\Seller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SellerService
{
    public function __construct(
        protected ContactService $contactService,
    ) {}

    /**
     * Reaproveita o vendedor pelo CPF/CNPJ ou cria o contato e o papel de
     * vendedor. Um vendedor existente mantém seus dados.
     *
     * @param  array<string, mixed>  $data
     */
    public function findOrCreateByDocument(array $data, ?User $creator, Tenant $tenant): Seller
    {
        return $tenant->run(function () use ($data, $creator, $tenant) {
            $document = preg_replace('/\D/', '', (string) $data['document']);
            $contact = $this->contactService->getContactByCpfCnpj($document, $tenant);

            $seller = $contact?->seller()->withTrashed()->first();

            if ($seller !== null) {
                if ($seller->trashed()) {
                    $seller->restore();
                }

                return $seller;
            }

            $contact ??= Contact::create([
                'type' => PersonType::from($data['person_type'])->value,
                'name_corporatereason' => $data['name'],
                'cpf_cnpj' => $document,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'cell_phone' => $data['phone'] ?? '',
            ]);

            $seller = $contact->seller()->create(array_merge(
                Arr::only($data, [
                    'marital_status',
                    'profession',
                    'declared_income',
                    'declares_income_tax',
                    'income_tax_notes',
                    'by_power_of_attorney',
                ]),
                ['creator_id' => $creator?->id],
            ));

            if (! empty($data['bank_account'])) {
                $seller->bankAccount()->create($data['bank_account']);
            }

            return $seller;
        });
    }

    /**
     * Vendedores da proposta com os dados editáveis na tela de edição
     * (contato, renda e conta bancária), já no formato do formulário.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForProposal(string $proposalId, Tenant $tenant): array
    {
        return $tenant->run(fn () => Proposal::findOrFail($proposalId)->sellers()
            ->with(['contact:id,type,name_corporatereason,cpf_cnpj,email,phone,cell_phone', 'bankAccount'])
            ->orderBy('proposal_seller.id')
            ->get()
            ->map(fn (Seller $seller) => [
                'id' => $seller->id,
                'person_type' => $seller->personType()->value,
                'document' => preg_replace('/\D/', '', $seller->contact->cpf_cnpj),
                'name' => $seller->contact->name_corporatereason,
                'email' => $seller->contact->email,
                'phone' => $seller->contact->cell_phone ?: $seller->contact->phone,
                'marital_status' => $seller->marital_status?->value,
                'profession' => $seller->profession,
                'declared_income' => $seller->declared_income,
                'declares_income_tax' => $seller->declares_income_tax,
                'income_tax_notes' => $seller->income_tax_notes,
                'by_power_of_attorney' => $seller->by_power_of_attorney,
                'bank_account' => $seller->bankAccount?->only(['bank_name', 'account_type', 'branch', 'number', 'notes']),
            ])
            ->all());
    }

    /**
     * Atualiza um vendedor da proposta. Nome/razão social, e-mail e telefone
     * ficam no contato, compartilhado com os demais papéis da mesma pessoa.
     * Documento e tipo de pessoa não mudam: são a chave que reaproveita o
     * vendedor entre propostas. Conta bancária sem dados é removida.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, string $proposalId, string $sellerId, Tenant $tenant): Seller
    {
        return $tenant->run(fn () => DB::transaction(function () use ($data, $proposalId, $sellerId) {
            $seller = Proposal::findOrFail($proposalId)->sellers()->with('contact')->findOrFail($sellerId);

            $seller->contact->update([
                'name_corporatereason' => $data['name'],
                'email' => $data['email'],
                'cell_phone' => $data['phone'] ?? '',
            ]);

            $seller->update(Arr::only($data, [
                'marital_status',
                'profession',
                'declared_income',
                'declares_income_tax',
                'income_tax_notes',
                'by_power_of_attorney',
            ]));

            if (empty($data['bank_account'])) {
                $seller->bankAccount()->delete();
            } else {
                $seller->bankAccount()->updateOrCreate([], $data['bank_account']);
            }

            return $seller;
        }));
    }
}
