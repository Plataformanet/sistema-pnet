<?php

namespace App\Services;

use App\Enums\PersonType;
use App\Models\Contact;
use App\Models\Seller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Arr;

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
}
