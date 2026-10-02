<?php

use App\Enums\RolesEnum;
use App\Models\Applicant;
use App\Models\Contact;
use App\Models\Seller;
use App\Models\User;
use App\Services\CompanySettingService;
use App\Services\ProposalPdfService;
use App\Support\DocumentMask;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = sharedTenant();
    config(['bucket.disk' => 'public']);
    Storage::fake('public');

    $this->client = userWithRole($this->tenant, RolesEnum::CLIENT, ['documents.proposals.view']);
    $this->sellerUser = userWithRole($this->tenant, RolesEnum::PROPERTY_SELLER, ['documents.proposals.view']);
    $this->proposal = createProposal($this->tenant);

    $this->tenant->run(function () {
        $this->proposal->applicants()->attach(Applicant::factory()->create([
            'user_id' => $this->client->id,
            'contact_id' => Contact::factory()->create([
                'name_corporatereason' => 'Maria Compradora',
                'cpf_cnpj' => '529.982.247-25',
                'email' => 'comprador@example.com',
                'phone' => '1133334444',
                'cell_phone' => '11988887777',
            ]),
        ]));

        $this->proposal->sellers()->attach(Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'contact_id' => Contact::factory()->create([
                'name_corporatereason' => 'João Vendedor',
                'cpf_cnpj' => '11144477735',
                'email' => 'vendedor@example.com',
            ]),
        ]));
    });
});

function infoPdfHtml(User $viewer): string
{
    $data = app(ProposalPdfService::class)->infoViewData((string) test()->proposal->id, $viewer, test()->tenant);

    return view('pdf.proposals.info', array_merge($data, app(CompanySettingService::class)->pdfBranding(test()->tenant)))->render();
}

test('o cliente vê os próprios dados e os do vendedor mascarados', function () {
    $html = infoPdfHtml($this->client);

    expect($html)->toContain('Maria Compradora', '529.982.247-25', 'comprador@example.com', '11988887777')
        ->toContain('João Vendedor', '***.444.777-**')
        ->not->toContain('111.444.777-35')
        ->not->toContain('11144477735')
        ->not->toContain('vendedor@example.com');
});

test('o vendedor do imóvel vê os próprios dados e os do comprador mascarados', function () {
    $html = infoPdfHtml($this->sellerUser);

    expect($html)->toContain('João Vendedor', 'vendedor@example.com')
        ->toContain('Maria Compradora', '***.982.247-**')
        ->not->toContain('529.982.247-25')
        ->not->toContain('comprador@example.com')
        ->not->toContain('11988887777');
});

test('a equipe vê os dados completos das duas partes', function () {
    $admin = userWithRole($this->tenant, RolesEnum::ADMIN);

    expect(infoPdfHtml($admin))->toContain('529.982.247-25', 'comprador@example.com', '11988887777', '11144477735', 'vendedor@example.com');
});

test('a máscara mantém só os dígitos do meio do CPF e do CNPJ', function (string $document, string $masked) {
    expect(DocumentMask::mask($document))->toBe($masked);
})->with([
    'CPF formatado' => ['529.982.247-25', '***.982.247-**'],
    'CPF só dígitos' => ['52998224725', '***.982.247-**'],
    'CNPJ' => ['11.222.333/0001-81', '**.222.333/****-**'],
    'inválido' => ['123', '***'],
]);
