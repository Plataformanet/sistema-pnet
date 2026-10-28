<?php

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Contact;
use App\Services\AccountPayableService;
use App\Services\ApplicantService;
use App\Services\ContactService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * Dados mínimos de cadastro de contato (cliente) com o documento informado.
 *
 * @return array<string, mixed>
 */
function contactPayload(string $document, array $overrides = []): array
{
    return array_merge([
        'type' => 'PF',
        'name_corporatereason' => 'Maria da Silva',
        'cpf_cnpj' => $document,
        'email' => 'maria@example.com',
        'phone' => '1133334444',
        'cell_phone' => '11999998888',
        'zip_code' => '01001-000',
        'street' => 'Rua A',
        'number' => '123',
        'complement' => null,
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
        'state' => 'SP',
    ], $overrides);
}

test('o documento do contato é gravado só com dígitos, qualquer que seja a origem', function () {
    $contact = $this->tenant->run(fn () => Contact::factory()->create(['cpf_cnpj' => '529.982.247-25']));

    expect($contact->cpf_cnpj)->toBe('52998224725')
        ->and($contact->fresh()->cpf_cnpj)->toBe('52998224725');
});

test('o request normaliza o documento para só dígitos', function () {
    $request = formRequest(StoreClientRequest::class, contactPayload('529.982.247-25'));

    expect($request->validated('cpf_cnpj'))->toBe('52998224725');
});

test('o banco recusa dois contatos com o mesmo documento, inclusive excluído', function () {
    $this->tenant->run(function () {
        $first = Contact::factory()->create(['cpf_cnpj' => '52998224725']);
        $first->delete();

        expect(fn () => Contact::factory()->create(['cpf_cnpj' => '529.982.247-25']))
            ->toThrow(QueryException::class);
    });
});

test('o update recusa o documento de outro contato mas aceita o do próprio', function () {
    [$mine, $other] = $this->tenant->run(fn () => [
        Contact::factory()->create(['cpf_cnpj' => '52998224725']),
        Contact::factory()->create(['cpf_cnpj' => '11144477735']),
    ]);

    $validate = function (Contact $contact, string $document) {
        $data = contactPayload($document);
        $request = UpdateClientRequest::create('/', 'PUT', $data);
        $route = new Route('PUT', 'registrations/clients/{id}', fn () => null);
        $route->bind($request);
        $route->setParameter('id', (string) $contact->id);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer(app());

        return $this->tenant->run(
            fn () => Validator::make($request->all(), $request->rules(), $request->messages())->errors()
        );
    };

    // `prepareForValidation` só roda no ciclo do FormRequest; aqui o documento já vai em dígitos.
    expect($validate($mine, '11144477735')->first('cpf_cnpj'))->toBe('Este CPF/CNPJ já pertence a outro contato.')
        ->and($validate($mine, '52998224725')->has('cpf_cnpj'))->toBeFalse();
});

test('a busca por documento encontra o contato com ou sem máscara', function () {
    $this->tenant->run(fn () => Contact::factory()->create(['cpf_cnpj' => '52998224725']));

    $service = app(ContactService::class);

    expect($service->getContactByCpfCnpj('529.982.247-25', $this->tenant)?->cpf_cnpj)->toBe('52998224725')
        ->and($service->getContactByCpfCnpj('52998224725', $this->tenant)?->cpf_cnpj)->toBe('52998224725')
        ->and($service->getContactByCpfCnpj('111.444.777-35', $this->tenant))->toBeNull();
});

test('store reaproveita o contato excluído em vez de duplicar o documento', function () {
    $deleted = $this->tenant->run(function () {
        $contact = Contact::factory()->create(['cpf_cnpj' => '52998224725', 'name_corporatereason' => 'Nome antigo']);
        $contact->address()->create([
            'zip_code' => '00000-000', 'street' => 'Rua Velha', 'number' => '1',
            'neighborhood' => 'Velho', 'city' => 'Rio', 'state' => 'RJ',
        ]);
        $contact->address()->delete();
        $contact->delete();

        return $contact;
    });

    $restored = app(ContactService::class)->store(contactPayload('529.982.247-25'), $this->tenant);

    $this->tenant->run(function () use ($deleted, $restored) {
        expect($restored->id)->toBe($deleted->id)
            ->and(Contact::where('cpf_cnpj', '52998224725')->count())->toBe(1)
            ->and($restored->fresh()->trashed())->toBeFalse()
            ->and($restored->fresh()->name_corporatereason)->toBe('Maria da Silva')
            ->and($restored->address()->count())->toBe(1)
            ->and($restored->address->street)->toBe('Rua A');
    });
});

test('o cadastro de solicitante reaproveita o contato excluído do mesmo CPF', function () {
    $deleted = $this->tenant->run(function () {
        $contact = Contact::factory()->create(['cpf_cnpj' => '52998224725']);
        $contact->delete();

        return $contact;
    });

    $applicant = app(ApplicantService::class)->findOrCreateByCpf([
        'cpf' => '529.982.247-25',
        'name' => 'Maria da Silva',
        'email' => 'maria@example.com',
        'phone' => '11999998888',
    ], $this->tenant);

    $this->tenant->run(function () use ($deleted, $applicant) {
        expect($applicant->contact_id)->toBe($deleted->id)
            ->and(Contact::where('cpf_cnpj', '52998224725')->count())->toBe(1);
    });
});

test('a busca de contatos ignora a máscara do documento e não casa tudo quando o termo não tem dígitos', function () {
    $this->tenant->run(function () {
        Contact::factory()->create(['cpf_cnpj' => '52998224725', 'name_corporatereason' => 'Maria']);
        Contact::factory()->create(['cpf_cnpj' => '11144477735', 'name_corporatereason' => 'José']);
    });

    $search = fn (string $term) => $this->tenant->run(
        fn () => app(AccountPayableService::class)->searchContact(Request::create('/', 'GET', ['search' => $term]))
            ->pluck('name_corporatereason')->all()
    );

    expect($search('529.982'))->toBe(['Maria'])
        ->and($search('José'))->toBe(['José']);
});
