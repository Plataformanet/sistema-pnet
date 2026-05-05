<?php

namespace App\Services;

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
}
