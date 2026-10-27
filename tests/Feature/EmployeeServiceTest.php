<?php

use App\Enums\ContactTypeEnum;
use App\Exceptions\ContactHasFinancialEntriesException;
use App\Models\AccountPayable;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Employee;
use App\Services\EmployeeService;

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
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    $this->tenant->run(function () {
        $employee = Employee::where('contact_id', $this->contact->id)->first();

        expect($employee)->not->toBeNull()
            ->and($employee->position)->toBe('Analista')
            ->and($employee->rg)->toBe('123456789');
    });
});

test('funcionário é criado ativo por padrão', function () {
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->first()->active)->toBeTrue();
    });
});

test('setActive inativa e reativa o funcionário preservando o registro', function () {
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    $afterInactivate = app(EmployeeService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    expect($afterInactivate)->toBeFalse();

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->first()->active)->toBeFalse();
    });

    $afterReactivate = app(EmployeeService::class)->setActive($this->tenant, (string) $this->contact->id, true);

    expect($afterReactivate)->toBeTrue();
});

test('findAll do funcionário expõe o campo active', function () {
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);
    app(EmployeeService::class)->setActive($this->tenant, (string) $this->contact->id, false);

    $employees = app(EmployeeService::class)->findAll($this->tenant);

    expect($employees->first()['active'])->toBeFalse();
});

test('update altera os dados sem duplicar o vínculo', function () {
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    app(EmployeeService::class)->update(
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
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    $originalId = $this->tenant->run(fn () => Employee::where('contact_id', $this->contact->id)->first()->id);

    app(EmployeeService::class)->destroy($this->tenant, (string) $this->contact->id);

    app(EmployeeService::class)->store($this->contact, employeePayload(['position' => 'Gerente']), $this->tenant);

    $this->tenant->run(function () use ($originalId) {
        $employee = Employee::where('contact_id', $this->contact->id)->first();

        expect($employee->id)->toBe($originalId)
            ->and($employee->trashed())->toBeFalse()
            ->and($employee->position)->toBe('Gerente')
            ->and(Employee::withTrashed()->where('contact_id', $this->contact->id)->count())->toBe(1);
    });
});

test('destroy remove o funcionário e também o contato órfão', function () {
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    app(EmployeeService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->toBeNull();
    });
});

test('destroy preserva o contato e o papel de cliente do mesmo contato', function () {
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    $this->tenant->run(fn () => $this->contact->client()->create([]));

    app(EmployeeService::class)->destroy($this->tenant, (string) $this->contact->id);

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->exists())->toBeFalse()
            ->and(Contact::find($this->contact->id))->not->toBeNull()
            ->and(Client::where('contact_id', $this->contact->id)->exists())->toBeTrue();
    });
});

test('destroy bloqueia a exclusão quando há lançamento financeiro como funcionário', function () {
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    createFinancialEntry($this->tenant, $this->contact->id, ContactTypeEnum::EMPLOYEE, AccountPayable::class);

    expect(fn () => app(EmployeeService::class)->destroy($this->tenant, (string) $this->contact->id))
        ->toThrow(ContactHasFinancialEntriesException::class);

    $this->tenant->run(function () {
        expect(Employee::where('contact_id', $this->contact->id)->exists())->toBeTrue();
    });
});

test('findAll ignora funcionários cujo contato foi excluído', function () {
    app(EmployeeService::class)->store($this->contact, employeePayload(), $this->tenant);

    $this->tenant->run(function () {
        $contact = Contact::factory()->create();
        $contact->employee()->create(employeePayload());
        $contact->delete();
    });

    $employees = app(EmployeeService::class)->findAll($this->tenant);

    expect($employees)->toHaveCount(1)
        ->and($employees->first()['contact']['id'])->toBe($this->contact->id)
        ->and($employees->first()['position'])->toBe('Analista');
});
