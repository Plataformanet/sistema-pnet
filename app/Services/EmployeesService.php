<?php

namespace App\Services;

use App\Enums\ContactTypeEnum;
use App\Exceptions\ContactHasFinancialEntriesException;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Tenant;
use DB;
use Illuminate\Support\Collection;

class EmployeesService
{
    public function store(Contact $contact, array $data, Tenant $tenant): Contact
    {
        return $tenant->run(fn () => DB::transaction(
            fn () => $this->persist($contact, $data)
        ));
    }

    public function update(Contact $contact, array $data, Tenant $tenant): Contact
    {
        return $tenant->run(fn () => DB::transaction(
            fn () => $this->persist($contact, $data)
        ));
    }

    /**
     * Garante o vínculo de funcionário do contato de forma idempotente.
     *
     * Um vínculo excluído é restaurado em vez de recriado, pois a linha
     * soft-deletada continua ocupando o índice `employees_contact_id_unique`.
     */
    private function persist(Contact $contact, array $data): Contact
    {
        $employee = $contact->employee()->withTrashed()->first() ?? $contact->employee()->make();

        $employee->fill([
            'rg' => $data['rg'],
            'birth_date' => $data['birth_date'],
            'position' => $data['position'],
            'salary' => $data['salary'],
            'hire_date' => $data['hire_date'],
        ]);

        $employee->deleted_at = null;
        $employee->save();

        return $contact;
    }

    /**
     * Remove o vínculo de funcionário do contato, preservando os demais papéis.
     *
     * @throws ContactHasFinancialEntriesException quando há lançamentos financeiros como funcionário.
     */
    public function destroy(Tenant $tenant, string $contactId): void
    {
        $tenant->run(function () use ($contactId) {
            DB::transaction(function () use ($contactId) {
                $employee = Employee::where('contact_id', $contactId)->firstOrFail();
                $contact = $employee->contact()->withTrashed()->firstOrFail();

                if ($contact->hasFinancialEntriesAs(ContactTypeEnum::EMPLOYEE)) {
                    throw new ContactHasFinancialEntriesException(ContactTypeEnum::EMPLOYEE);
                }

                $employee->delete();

                $contact->deleteIfOrphaned();
            });
        });
    }

    /**
     * Alterna a disponibilidade do funcionário sem excluí-lo, preservando o histórico.
     *
     * @return bool O novo estado de `active` após a alteração.
     */
    public function setActive(Tenant $tenant, string $contactId, bool $active): bool
    {
        return $tenant->run(function () use ($contactId, $active) {
            $employee = Employee::where('contact_id', $contactId)->firstOrFail();

            $employee->update(['active' => $active]);

            return $employee->active;
        });
    }

    public function findByContactId(string $id, Tenant $tenant): Employee
    {
        return $tenant->run(fn () => Employee::with(['contact', 'contact.address'])->where('contact_id', $id)->firstOrFail());
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => Employee::query()
            ->whereHas('contact')
            ->with('contact:id,name_corporatereason,email,cpf_cnpj')
            ->get()
            ->map(fn (Employee $employee) => [
                'active' => $employee->active,
                'contact' => [
                    'id' => $employee->contact->id,
                    'name_corporatereason' => $employee->contact->name_corporatereason,
                    'email' => $employee->contact->email,
                    'cpf_cnpj' => $employee->contact->cpf_cnpj,
                ],
                'position' => $employee->position,
            ]));
    }
}
