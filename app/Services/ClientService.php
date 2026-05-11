<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Tenant;
use DB;

class ClientService
{

    public function store(Contact $contact, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($contact, $data) {
            DB::beginTransaction();

            try {

                $client = $contact->client()->create([]);

                DB::commit();

                return $client;

            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function update(Contact $contact, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($contact, $data) {
            DB::beginTransaction();

            try {

                $client = $contact->client()->update([]);

                DB::commit();

                return $client;

            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function findById(string $id, Tenant $tenant)
    {
        return $tenant->run(function () use ($id) {
            return Client::with(['contact', 'contact.address'])->where('contact_id', $id)->firstOrFail();
        });
    }

    public function findAll(Tenant $tenant)
    {
        return $tenant->run(function () {
            return Client::with(['contact'])->get()->map(function ($client) {
                return [
                    'id'       => $client->contact->id,
                    'name'     => $client->contact->name_corporatereason,
                    'email'    => $client->contact->email,
                    'cpf_cnpj' => $client->contact->cpf_cnpj,
                ];
            });
        });
    }
}
