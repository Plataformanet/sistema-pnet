<?php

use App\Enums\DocumentOwner;
use App\Enums\RolesEnum;
use App\Models\Applicant;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\Seller;
use App\Policies\ProposalPolicy;
use App\Services\ProposalQueryService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->tenant = sharedTenant();
    $this->policy = app(ProposalPolicy::class);

    $this->linked = createProposal($this->tenant);
    $this->other = createProposal($this->tenant);
});

test('administrador com permissão vê, edita e exclui qualquer proposta', function () {
    $admin = userWithRole($this->tenant, RolesEnum::ADMIN, ['documents.proposals.view', 'documents.proposals.edit', 'documents.proposals.delete']);

    $this->tenant->run(function () use ($admin) {
        expect($this->policy->view($admin, $this->other))->toBeTrue()
            ->and($this->policy->update($admin, $this->other))->toBeTrue()
            ->and($this->policy->delete($admin, $this->other))->toBeTrue();
    });
});

test('cargo sem a permissão tem o acesso negado', function () {
    $manager = userWithRole($this->tenant, RolesEnum::MANAGER, ['documents.proposals.view']);

    $this->tenant->run(function () use ($manager) {
        expect($this->policy->view($manager, $this->other))->toBeTrue()
            ->and($this->policy->update($manager, $this->other))->toBeFalse()
            ->and($this->policy->delete($manager, $this->other))->toBeFalse()
            ->and($this->policy->manageTimeline($manager, $this->other))->toBeFalse()
            ->and($this->policy->manageFinancial($manager, $this->other))->toBeFalse();
    });
});

test('o cargo Analista tem todas as permissões do grupo Documentações', function () {
    $this->tenant->run(function () {
        $documentsPermissions = Permission::where('name', 'like', 'documents.%')->pluck('name')->sort()->values();
        $analystPermissions = Role::findByName(RolesEnum::ANALYST->label(), 'web')->permissions()
            ->where('name', 'like', 'documents.%')->pluck('name')->sort()->values();

        expect($documentsPermissions)->not->toBeEmpty()
            ->and($analystPermissions->all())->toBe($documentsPermissions->all());
    });
});

test('parceiro só vê as propostas a que está vinculado ou que criou', function () {
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER, ['documents.proposals.view']);
    $created = createProposal($this->tenant, ['creator_id' => $partner->id, 'analyst_id' => $partner->id]);

    $this->tenant->run(function () use ($partner, $created) {
        $this->linked->partners()->attach($partner->id);

        expect($this->policy->view($partner, $this->linked))->toBeTrue()
            ->and($this->policy->view($partner, $created))->toBeTrue()
            ->and($this->policy->view($partner, $this->other))->toBeFalse()
            ->and(Proposal::visibleTo($partner)->pluck('id')->all())->toEqualCanonicalizing([$this->linked->id, $created->id]);
    });
});

test('vendedor do imóvel só vê as propostas em que é vendedor', function () {
    $sellerUser = userWithRole($this->tenant, RolesEnum::PROPERTY_SELLER, ['documents.proposals.view']);

    $this->tenant->run(function () use ($sellerUser) {
        $this->linked->sellers()->attach(Seller::factory()->create(['user_id' => $sellerUser->id]));

        expect($this->policy->view($sellerUser, $this->linked))->toBeTrue()
            ->and($this->policy->view($sellerUser, $this->other))->toBeFalse();
    });
});

test('cliente só vê as próprias propostas e só nelas anexa comprovante', function () {
    $client = userWithRole($this->tenant, RolesEnum::CLIENT, ['documents.proposals.view']);

    $this->tenant->run(function () use ($client) {
        $this->linked->applicants()->attach(Applicant::factory()->create(['user_id' => $client->id]));

        expect($this->policy->view($client, $this->linked))->toBeTrue()
            ->and($this->policy->view($client, $this->other))->toBeFalse()
            ->and($this->policy->uploadProof($client, $this->linked))->toBeTrue()
            ->and($this->policy->uploadProof($client, $this->other))->toBeFalse()
            ->and($this->policy->update($client, $this->linked))->toBeFalse();
    });
});

test('parceiro só baixa documentos de pessoas e do imóvel', function () {
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER, ['documents.proposals.view', 'documents.proposal_documents.view']);

    $this->tenant->run(function () use ($partner) {
        $this->linked->partners()->attach($partner->id);

        $buyerDocument = new ProposalDocument(['owner' => DocumentOwner::BUYER]);
        $generalDocument = new ProposalDocument(['owner' => DocumentOwner::GENERAL]);

        expect($this->policy->downloadDocument($partner, $this->linked, $buyerDocument))->toBeTrue()
            ->and($this->policy->downloadDocument($partner, $this->linked, $generalDocument))->toBeFalse()
            ->and($this->policy->downloadDocument($partner, $this->other, $buyerDocument))->toBeFalse();
    });
});

test('a listagem e os contadores respeitam a visibilidade do cargo', function () {
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER, ['documents.proposals.view']);
    $this->tenant->run(fn () => $this->linked->partners()->attach($partner->id));

    $service = app(ProposalQueryService::class);
    $ids = collect($service->paginate([], $partner, $this->tenant)->items())->pluck('id')->all();
    $counts = $service->statusCounts($partner, $this->tenant);

    expect($ids)->toBe([$this->linked->id])
        ->and(array_sum($counts))->toBe(1);
});

test('cliente não vê documentos do vendedor e vendedor do imóvel não vê os do comprador', function () {
    $permissions = ['documents.proposals.view', 'documents.proposal_documents.view'];
    $client = userWithRole($this->tenant, RolesEnum::CLIENT, $permissions);
    $sellerUser = userWithRole($this->tenant, RolesEnum::PROPERTY_SELLER, $permissions);

    $this->tenant->run(function () use ($client, $sellerUser) {
        $this->linked->applicants()->attach(Applicant::factory()->create(['user_id' => $client->id]));
        $this->linked->sellers()->attach(Seller::factory()->create(['user_id' => $sellerUser->id]));

        $buyerDocument = new ProposalDocument(['owner' => DocumentOwner::BUYER]);
        $sellerDocument = new ProposalDocument(['owner' => DocumentOwner::SELLER]);
        $propertyDocument = new ProposalDocument(['owner' => DocumentOwner::PROPERTY]);
        $this->linked->setRelation('documents', collect([$buyerDocument, $sellerDocument, $propertyDocument]));
        $service = app(ProposalQueryService::class);

        expect($this->policy->downloadDocument($client, $this->linked, $buyerDocument))->toBeTrue()
            ->and($this->policy->downloadDocument($client, $this->linked, $sellerDocument))->toBeFalse()
            ->and($this->policy->downloadDocument($sellerUser, $this->linked, $sellerDocument))->toBeTrue()
            ->and($this->policy->downloadDocument($sellerUser, $this->linked, $buyerDocument))->toBeFalse()
            ->and($service->documentsFor($this->linked, $client)->pluck('owner')->all())->toBe([DocumentOwner::BUYER, DocumentOwner::PROPERTY])
            ->and($service->documentsFor($this->linked, $sellerUser)->pluck('owner')->all())->toBe([DocumentOwner::SELLER, DocumentOwner::PROPERTY]);
    });
});

test('a visibilidade da proposta é consultada uma única vez por usuário', function () {
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER, ['documents.proposals.view']);

    $queries = $this->tenant->run(function () use ($partner) {
        $partner->loadMissing('roles');
        DB::enableQueryLog();

        $this->linked->isVisibleTo($partner);
        $this->linked->isVisibleTo($partner);

        return count(DB::getQueryLog());
    });

    expect($queries)->toBe(1);
});
