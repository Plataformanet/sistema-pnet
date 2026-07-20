<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Tenant;
use DB;

class ContactService
{
    public function store(array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($data) {
            return DB::transaction(function () use ($data) {

                $contact = Contact::create([
                    'type' => $data['type'] ?? 'PF',
                    'name_corporatereason' => $data['name_corporatereason'],
                    'fantasy_name' => $data['fantasy_name'] ?? null,
                    'cpf_cnpj' => $data['cpf_cnpj'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'cell_phone' => $data['cell_phone'],
                ]);

                $contact->address()->create([
                    'zip_code' => $data['zip_code'],
                    'street' => $data['street'],
                    'number' => $data['number'],
                    'complement' => $data['complement'],
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                ]);

                return $contact;

            });
        });
    }

    public function update(array $data, Tenant $tenant, string $id)
    {
        return $tenant->run(function () use ($data, $id) {
            return DB::transaction(function () use ($data, $id) {

                $contact = Contact::findOrFail($id);

                $contact->update([
                    'type' => $data['type'] ?? 'PF',
                    'name_corporatereason' => $data['name_corporatereason'],
                    'fantasy_name' => $data['fantasy_name'] ?? null,
                    'cpf_cnpj' => $data['cpf_cnpj'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'cell_phone' => $data['cell_phone'],
                ]);

                $contact->address()->update([
                    'zip_code' => $data['zip_code'],
                    'street' => $data['street'],
                    'number' => $data['number'],
                    'complement' => $data['complement'],
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                ]);

                return $contact;
            });
        });
    }

    public function getContactByCpfCnpj(string $cpfCnpj, Tenant $tenant)
    {
        return $tenant->run(function () use ($cpfCnpj) {
            $clean = preg_replace('/\D/', '', $cpfCnpj);

            $formatted = null;
            if (strlen($clean) === 11) {
                $formatted = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", '$1.$2.$3-$4', $clean);
            } elseif (strlen($clean) === 14) {
                $formatted = preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", '$1.$2.$3/$4-$5', $clean);
            }

            return Contact::with('address')
                ->where(function ($query) use ($clean, $cpfCnpj, $formatted) {
                    $query->where('cpf_cnpj', $clean)
                        ->orWhere('cpf_cnpj', $cpfCnpj);
                    if ($formatted) {
                        $query->orWhere('cpf_cnpj', $formatted);
                    }
                })
                ->first();
        });
    }
}
