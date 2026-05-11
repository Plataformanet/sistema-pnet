<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Employee;
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
        return Employee::with(['contact', 'contact.address'])->where('contact_id', $id)->firstOrFail();
    }

    public function findAll()
    {
        return Employee::with(['contact'])->get()->map(function ($employee) {
            return [
                'id'       => $employee->contact->id,
                'name'     => $employee->contact->name_corporatereason,
                'email'    => $employee->contact->email,
                'cpf_cnpj' => $employee->contact->cpf_cnpj,
            ];
        });
    }
}
