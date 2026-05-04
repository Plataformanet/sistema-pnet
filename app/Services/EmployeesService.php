<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Tenant;

class EmployeesService
{
    public function store(Contact $contact, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($contact, $data) {
            $contact->employee()->create([
                'contact_id' => $contact->id,
                'rg'         => $data['rg'],
                'birth_date' => $data['birth_date'],
                'position'   => $data['position'],
                'salary'     => $data['salary'],
                'hire_date'  => $data['hire_date'],
            ]);
        });
    }
}
