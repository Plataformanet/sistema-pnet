<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Tenant;
use DB;

class ContactService
{
    public function store(array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($data) {
            return DB::transaction(function () use ($data) {

                $trashed = $this->findTrashedByDocument($data['cpf_cnpj']);

                if ($trashed !== null) {
                    return $this->restore($trashed, $data);
                }

                $contact = Contact::create([
                    'type' => $data['type'] ?? 'PF',
                    'name_corporatereason' => $data['name_corporatereason'],
                    'fantasy_name' => $data['fantasy_name'] ?? null,
                    'cpf_cnpj' => $data['cpf_cnpj'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'cell_phone' => $data['cell_phone'],
                ]);

                $contact->address()->create([
                    'zip_code' => $data['zip_code'],
                    'street' => $data['street'],
                    'number' => $data['number'],
                    'complement' => $data['complement'],
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                ]);

                return $contact;

            });
        });
    }

    public function update(array $data, Tenant $tenant, string $id)
    {
        return $tenant->run(function () use ($data, $id) {
            return DB::transaction(function () use ($data, $id) {

                $contact = Contact::findOrFail($id);

                $contact->update([
                    'type' => $data['type'] ?? 'PF',
                    'name_corporatereason' => $data['name_corporatereason'],
                    'fantasy_name' => $data['fantasy_name'] ?? null,
                    'cpf_cnpj' => $data['cpf_cnpj'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'cell_phone' => $data['cell_phone'],
                ]);

                $contact->address()->update([
                    'zip_code' => $data['zip_code'],
                    'street' => $data['street'],
                    'number' => $data['number'],
                    'complement' => $data['complement'],
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                ]);

                return $contact;
            });
        });
    }

    /**
     * Busca o contato ativo pelo documento, com ou sem máscara. O documento é
     * gravado só com dígitos (RN-CAD-01), então a busca também.
     */
    public function getContactByCpfCnpj(string $cpfCnpj, Tenant $tenant)
    {
        return $tenant->run(function () use ($cpfCnpj) {
            return Contact::with('address')
                ->where('cpf_cnpj', preg_replace('/\D/', '', $cpfCnpj))
                ->first();
        });
    }

    /**
     * Restaura o contato excluído (soft delete) que tem o documento, ou devolve
     * nulo. O `unique` do documento cobre os excluídos, então reaproveitar o
     * registro é o único jeito de cadastrar de novo um documento já usado.
     * Chamar dentro de `$tenant->run()`.
     */
    public function restoreByDocument(string $cpfCnpj): ?Contact
    {
        $trashed = $this->findTrashedByDocument($cpfCnpj);

        if ($trashed === null) {
            return null;
        }

        $trashed->restore();
        $trashed->address()->onlyTrashed()->restore();

        return $trashed;
    }

    private function findTrashedByDocument(string $cpfCnpj): ?Contact
    {
        return Contact::onlyTrashed()
            ->where('cpf_cnpj', preg_replace('/\D/', '', $cpfCnpj))
            ->first();
    }

    /**
     * Restaura o contato excluído e o atualiza com os dados do novo cadastro.
     *
     * @param  array<string, mixed>  $data
     */
    private function restore(Contact $contact, array $data): Contact
    {
        $contact->restore();
        $contact->update([
            'type' => $data['type'] ?? 'PF',
            'name_corporatereason' => $data['name_corporatereason'],
            'fantasy_name' => $data['fantasy_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'cell_phone' => $data['cell_phone'],
        ]);

        $contact->address()->withTrashed()->restore();
        $contact->address()->updateOrCreate([], [
            'zip_code' => $data['zip_code'],
            'street' => $data['street'],
            'number' => $data['number'],
            'complement' => $data['complement'],
            'neighborhood' => $data['neighborhood'],
            'city' => $data['city'],
            'state' => $data['state'],
        ]);

        return $contact;
    }
}
