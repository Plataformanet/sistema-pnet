<?php

namespace App\Services;

use App\Models\Clients;
use App\Models\Contact;
use App\Models\Tenant;

class ClientService
{

    public function store(Contact $contact, array $data, Tenant $tenant): Clients
    {
        return $tenant->run(function () use ($contact, $data) {
            $contact->client()->create([
                'contact_id' => $contact->id,
            ]);
        });
    }
}
