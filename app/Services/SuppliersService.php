<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Tenant;

class SuppliersService
{
    public function store(Contact $contact, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($contact, $data) {
            $contact->supplier()->create([
                'contact_id'         => $contact->id,
                'responsible_person' => $data['responsible_person'],
                'description'        => $data['description'],
                'supply_category'    => $data['supply_category'],
            ]);
        });
    }
}
