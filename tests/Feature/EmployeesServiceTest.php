<?php

use App\Enums\ContactTypeEnum;
use App\Exceptions\ContactHasFinancialEntriesException;
use App\Models\AccountPayable;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Employee;
use App\Services\EmployeesService;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->contact = $this->tenant->run(fn () => Contact::factory()->create());
});

function employeePayload(array $overrides = []): array
{
    return array_merge([
        'rg' => '123456789',
        'birth_date' => '1990-05-10',
        'position' => 'Analista',
        'salary' => 500000,
        'hire_date' => '2020-01-15',
    ], $overrides);
}

test('store cria o vínculo de funcionário com os dados informados', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    $this->tenant->run(function () {
        $employee = Employee::where('contact_id', $this->contact->id)->first();

        expect($employee)->not->toBeNull()
            ->and($employee->position)->toBe('Analista')
            ->and($employee->rg)->toBe('123456789');
    });
});

test('funcionário é criado ativo por padrão', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->first()->active)->toBeTrue();
    });
});

test('setActive inativa e reativa o funcionário preservando o registro', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    $afterInactivate = app(EmployeesService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    expect($afterInactivate)->toBeFalse();

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->first()->active)->toBeFalse();
    });

    $afterReactivate = app(EmployeesService::class)->setActive($this->tenant, (string) $this->contact->id, true);

    expect($afterReactivate)->toBeTrue();
});

test('findAll do funcionário expõe o campo active', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);
    app(EmployeesService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    $employees = app(EmployeesService::class)->findAll($this->tenant);

    expect($employees->first()['active'])->toBeFalse();
});

test('update altera os dados sem duplicar o vínculo', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    app(EmployeesService::class)->update(
        $this->contact,
        employeePayload(['position' => 'Gerente']),
        $this->tenant
    );

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->count())->toBe(1)
            ->and(Employee::where('contact_id', $this->contact->id)->first()->position)->toBe('Gerente');
    });
});

test('store restaura o vínculo soft-deletado em vez de estourar o índice único', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    $originalId = $this->tenant->run(fn () => Employee::where('contact_id', $this->contact->id)->first()->id);

    app(EmployeesService::class)->destroy($this->tenant, (string) $this->contact->id);

    app(EmployeesService::class)->store($this->contact, employeePayload(['position' => 'Gerente']), $this->tenant);

    $this->tenant->run(function () use ($originalId) {
        $employee = Employee::where('contact_id', $this->contact->id)->first();

        expect($employee->id)->toBe($originalId)
            ->and($employee->trashed())->toBeFalse()
            ->and($employee->position)->toBe('Gerente')
            ->and(Employee::withTrashed()->where('contact_id', $this->contact->id)->count())->toBe(1);
    });
});

test('destroy remove o funcionário e também o contato órfão', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    app(EmployeesService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->toBeNull();
    });
});

test('destroy preserva o contato e o papel de cliente do mesmo contato', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    $this->tenant->run(fn () => $this->contact->client()->create([]));

    app(EmployeesService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->not->toBeNull()
            ->and(Client::where('contact_id', $this->contact->id)->exists())->toBeTrue();
    });
});

test('destroy bloqueia a exclusão quando há lançamento financeiro como funcionário', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    createFinancialEntry($this->tenant, $this->contact->id, ContactTypeEnum::EMPLOYEE, AccountPayable::class);

    expect(fn () => app(EmployeesService::class)->destroy($this->tenant, (string) $this->contact->id))
        ->toThrow(ContactHasFinancialEntriesException::class);

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->exists())->toBeTrue();
    });
});

test('findAll ignora funcionários cujo contato foi excluído', function () {
    app(EmployeesService::class)->store($this->contact, employeePayload(), $this->tenant);

    $this->tenant->run(function () {
        $contact = Contact::factory()->create();
        $contact->employee()->create(employeePayload());
        $contact->delete();
    });

    $employees = app(EmployeesService::class)->findAll($this->tenant);

    expect($employees)->toHaveCount(1)
        ->and($employees->first()['contact']['id'])->toBe($this->contact->id)
        ->and($employees->first()['position'])->toBe('Analista');
});
