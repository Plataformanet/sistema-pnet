<?php

namespace App\Services;

use App\Enums\TypeContactEnum;
use App\Models\Contact;
use App\Models\Tenant;

class ContactService
{
    public function store(array $data, Tenant $tenant): Contact
    {
        return $tenant->run(function () use ($data) {
            $contact = Contact::create([
                'type'                 => $data['type'],
                'name_corporatereason' => $data['name_corporatereason'],
                'fantasy_name'         => $data['fantasy_name'],
                'cpf_cnpj'             => $data['cpf_cnpj'],
                'email'                => $data['email'],
                'phone'                => $data['phone'],
                'cell_phone'           => $data['cell_phone'],
            ]);

            $contact->address()->create([
                'zip_code'     => $data['zip_code'],
                'street'       => $data['street'],
                'number'       => $data['number'],
                'complement'   => $data['complement'],
                'neighborhood' => $data['neighborhood'],
                'city'         => $data['city'],
                'state'        => $data['state'],
            ]);

            return $contact;
        });
    }

    public function update(array $data, $id)
    {
    }

    public function destroy(string $id)
    {
    }

    public function findById(string $id)
    {
    }

    public function findAll()
    {
    }
}
