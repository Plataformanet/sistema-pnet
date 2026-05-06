<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Tenant;
use DB;

class EmployeesService
{
    public function store(Contact $contact, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($contact, $data) {

            DB::beginTransaction();

            try {

                $contact->employee()->create([
                    'rg'         => $data['rg'],
                    'birth_date' => $data['birth_date'],
                    'position'   => $data['position'],
                    'salary'     => $data['salary'],
                    'hire_date'  => $data['hire_date'],
                ]);

                DB::commit();

                return $contact;

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
                $contact->employee()->update([
                    'rg'         => $data['rg'],
                    'birth_date' => $data['birth_date'],
                    'position'   => $data['position'],
                    'salary'     => $data['salary'],
                    'hire_date'  => $data['hire_date'],
                ]);

                DB::commit();

                return $contact;
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function findById(string $id)
    {
        return Contact::with(['employee', 'address'])->find($id);
    }

    public function findAll()
    {
        return Contact::with('employee')->get()->map(function ($contact) {
            return [
                'id'       => $contact->id,
                'name'     => $contact->name_corporatereason,
                'email'    => $contact->email,
                'cpf_cnpj' => $contact->cpf_cnpj,
            ];
        });
    }
}
