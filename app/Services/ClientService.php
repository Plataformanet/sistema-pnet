<?php

namespace App\Services;

use App\Enums\ContactTypeEnum;
use App\Exceptions\ContactHasFinancialEntriesException;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Tenant;
use DB;
use Illuminate\Support\Collection;

class ClientService
{
    public function store(Contact $contact, array $data, Tenant $tenant): Client
    {
        return $tenant->run(fn () => DB::transaction(
            fn () => $this->persist($contact)
        ));
    }

    public function update(Contact $contact, array $data, Tenant $tenant): Client
    {
        return $tenant->run(fn () => DB::transaction(
            fn () => $this->persist($contact)
        ));
    }

    /**
     * Garante o vínculo de cliente do contato de forma idempotente.
     *
     * Um vínculo excluído é restaurado em vez de recriado, pois a linha
     * soft-deletada continua ocupando o índice `clients_contact_id_unique`.
     */
    private function persist(Contact $contact): Client
    {
        $client = $contact->client()->withTrashed()->first() ?? $contact->client()->make();

        $client->deleted_at = null;
        $client->save();

        return $client;
    }

    /**
     * Remove o vínculo de cliente do contato, preservando os demais papéis.
     *
     * @throws ContactHasFinancialEntriesException quando há lançamentos financeiros como cliente.
     */
    public function destroy(Tenant $tenant, string $contactId): void
    {
        $tenant->run(function () use ($contactId) {
            DB::transaction(function () use ($contactId) {
                $client = Client::where('contact_id', $contactId)->firstOrFail();
                $contact = $client->contact()->withTrashed()->firstOrFail();

                if ($contact->hasFinancialEntriesAs(ContactTypeEnum::CLIENT)) {
                    throw new ContactHasFinancialEntriesException(ContactTypeEnum::CLIENT);
                }

                $client->delete();

                $contact->deleteIfOrphaned();
            });
        });
    }

    /**
     * Alterna a disponibilidade do cliente sem excluí-lo, preservando o histórico.
     *
     * @return bool O novo estado de `active` após a alteração.
     */
    public function setActive(Tenant $tenant, string $contactId, bool $active): bool
    {
        return $tenant->run(function () use ($contactId, $active) {
            $client = Client::where('contact_id', $contactId)->firstOrFail();

            $client->update(['active' => $active]);

            return $client->active;
        });
    }

    public function findByContactId(string $id, Tenant $tenant): Client
    {
        return $tenant->run(fn () => Client::with(['contact', 'contact.address'])->where('contact_id', $id)->firstOrFail());
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => Client::query()
            ->whereHas('contact')
            ->with('contact:id,name_corporatereason,email,cpf_cnpj')
            ->get()
            ->map(fn (Client $client) => [
                'active' => $client->active,
                'contact' => [
                    'id' => $client->contact->id,
                    'name_corporatereason' => $client->contact->name_corporatereason,
                    'email' => $client->contact->email,
                    'cpf_cnpj' => $client->contact->cpf_cnpj,
                ],
            ]));
    }
}
