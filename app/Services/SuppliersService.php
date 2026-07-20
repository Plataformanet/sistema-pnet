<?php

namespace App\Services;

use App\Enums\ContactTypeEnum;
use App\Exceptions\ContactHasFinancialEntriesException;
use App\Models\Contact;
use App\Models\Supplier;
use App\Models\Tenant;
use DB;
use Illuminate\Support\Collection;

class SuppliersService
{
    public function store(Contact $contact, array $data, Tenant $tenant): Contact
    {
        return $tenant->run(fn () => DB::transaction(
            fn () => $this->persist($contact, $data)
        ));
    }

    public function update(Contact $contact, array $data, Tenant $tenant): Contact
    {
        return $tenant->run(fn () => DB::transaction(
            fn () => $this->persist($contact, $data)
        ));
    }

    /**
     * Garante o vínculo de fornecedor do contato de forma idempotente.
     *
     * Um vínculo excluído é restaurado em vez de recriado, pois a linha
     * soft-deletada continua ocupando o índice `suppliers_contact_id_unique`.
     */
    private function persist(Contact $contact, array $data): Contact
    {
        $supplier = $contact->supplier()->withTrashed()->first() ?? $contact->supplier()->make();

        $supplier->fill([
            'responsible_person' => $data['responsible_person'],
            'description' => $data['description'],
            'supply_category' => $data['supply_category'],
        ]);

        $supplier->deleted_at = null;
        $supplier->save();

        return $contact;
    }

    /**
     * Remove o vínculo de fornecedor do contato, preservando os demais papéis.
     *
     * @throws ContactHasFinancialEntriesException quando há lançamentos financeiros como fornecedor.
     */
    public function destroy(Tenant $tenant, string $contactId): void
    {
        $tenant->run(function () use ($contactId) {
            DB::transaction(function () use ($contactId) {
                $supplier = Supplier::where('contact_id', $contactId)->firstOrFail();
                $contact = $supplier->contact()->withTrashed()->firstOrFail();

                if ($contact->hasFinancialEntriesAs(ContactTypeEnum::SUPPLIER)) {
                    throw new ContactHasFinancialEntriesException(ContactTypeEnum::SUPPLIER);
                }

                $supplier->delete();

                $contact->deleteIfOrphaned();
            });
        });
    }

    /**
     * Alterna a disponibilidade do fornecedor sem excluí-lo, preservando o histórico.
     *
     * @return bool O novo estado de `active` após a alteração.
     */
    public function setActive(Tenant $tenant, string $contactId, bool $active): bool
    {
        return $tenant->run(function () use ($contactId, $active) {
            $supplier = Supplier::where('contact_id', $contactId)->firstOrFail();

            $supplier->update(['active' => $active]);

            return $supplier->active;
        });
    }

    public function findByContactId(string $id, Tenant $tenant): Supplier
    {
        return $tenant->run(fn () => Supplier::with(['contact', 'contact.address'])->where('contact_id', $id)->firstOrFail());
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => Supplier::query()
            ->whereHas('contact')
            ->with('contact:id,name_corporatereason,email,cpf_cnpj')
            ->get()
            ->map(fn (Supplier $supplier) => [
                'active' => $supplier->active,
                'contact' => [
                    'id' => $supplier->contact->id,
                    'name_corporatereason' => $supplier->contact->name_corporatereason,
                    'email' => $supplier->contact->email,
                    'cpf_cnpj' => $supplier->contact->cpf_cnpj,
                ],
                'supply_category' => $supplier->supply_category,
            ]));
    }
}
