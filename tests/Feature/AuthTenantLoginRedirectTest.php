<?php

use App\Enums\RolesEnum;
use App\Http\Controllers\Auth\AuthTenantController;
use App\Http\Requests\StoreTenantLoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * Executa o login do tenant simulando a sessão da requisição.
 */
function loginRequest(string $email, string $password, bool $remember = false): RedirectResponse
{
    $request = formRequest(StoreTenantLoginRequest::class, [
        'email' => $email,
        'password' => $password,
        'remember' => $remember,
    ]);

    $request->setLaravelSession(session()->driver());

    return app(AuthTenantController::class)->login($request);
}

it('redireciona para a url pretendida guardada na sessão após o login', function () {
    $this->tenant->run(function () {
        User::factory()->create([
            'email' => 'usuario@teste.com',
            'password' => Hash::make('senha-secreta'),
        ]);
    });

    session()->put('url.intended', 'http://acme.localhost/drive');

    $response = loginRequest('usuario@teste.com', 'senha-secreta');

    expect($response->getTargetUrl())->toBe('http://acme.localhost/drive');
});

it('redireciona para o dashboard quando não há url pretendida', function () {
    $this->tenant->run(function () {
        User::factory()->create([
            'email' => 'usuario@teste.com',
            'password' => Hash::make('senha-secreta'),
        ]);
    });

    $response = loginRequest('usuario@teste.com', 'senha-secreta');

    expect($response->getTargetUrl())->toBe(route('tenant.dashboard'));
});

it('redireciona cargo externo às propostas para a lista de propostas', function (RolesEnum $role) {
    $user = userWithRole($this->tenant, $role, ['documents.proposals.view']);

    $this->tenant->run(fn () => $user->forceFill(['password' => Hash::make('senha-secreta')])->save());

    $response = loginRequest($user->email, 'senha-secreta');

    expect($response->getTargetUrl())->toBe(route('tenant.documents.proposals.list'));
})->with([RolesEnum::CLIENT, RolesEnum::PROPERTY_SELLER, RolesEnum::PARTNER]);

it('mantém o usuário conectado quando a opção é marcada no login', function (bool $remember) {
    $this->tenant->run(function () {
        User::factory()->create([
            'email' => 'usuario@teste.com',
            'password' => Hash::make('senha-secreta'),
        ]);
    });

    loginRequest('usuario@teste.com', 'senha-secreta', $remember);

    expect(Cookie::hasQueued(Auth::guard()->getRecallerName()))->toBe($remember);
})->with([
    'marcado' => true,
    'desmarcado' => false,
]);
