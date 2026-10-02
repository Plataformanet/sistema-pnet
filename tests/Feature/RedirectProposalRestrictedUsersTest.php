<?php

use App\Enums\RolesEnum;
use App\Http\Middleware\RedirectProposalRestrictedUsers;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * Passa uma requisição autenticada pelo middleware e devolve a resposta.
 */
function throughRestrictedMiddleware(User $user): Response
{
    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    return (new RedirectProposalRestrictedUsers)->handle($request, fn () => response('ok'));
}

it('manda cargo externo às propostas para a lista de propostas', function (RolesEnum $role) {
    $user = userWithRole($this->tenant, $role, ['documents.proposals.view']);

    $response = $this->tenant->run(fn () => throughRestrictedMiddleware($user));

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('tenant.documents.proposals.list'));
})->with([RolesEnum::CLIENT, RolesEnum::PROPERTY_SELLER, RolesEnum::PARTNER]);

it('deixa a equipe interna seguir para a tela', function (RolesEnum $role) {
    $user = userWithRole($this->tenant, $role);

    $response = $this->tenant->run(fn () => throughRestrictedMiddleware($user));

    expect($response->getContent())->toBe('ok');
})->with([RolesEnum::ADMIN, RolesEnum::ANALYST]);

it('deixa seguir quem tem cargo externo junto com um cargo interno', function () {
    $user = userWithRole($this->tenant, RolesEnum::CLIENT);

    $response = $this->tenant->run(function () use ($user) {
        $user->assignRole(Role::firstOrCreate(['name' => RolesEnum::ANALYST->label(), 'guard_name' => 'web']));

        return throughRestrictedMiddleware($user->fresh());
    });

    expect($response->getContent())->toBe('ok');
});

test('dashboard e CRM passam pelo middleware de usuários externos', function (string $routeName) {
    expect(Route::getRoutes()->getByName($routeName)->gatherMiddleware())
        ->toContain(RedirectProposalRestrictedUsers::class);
})->with(['tenant.dashboard', 'tenant.crm.kanban', 'tenant.crm.list']);
