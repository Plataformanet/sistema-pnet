<?php

use App\Http\Controllers\Auth\AuthTenantController;
use App\Http\Requests\StoreTenantLoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * Executa o login do tenant simulando a sessão da requisição.
 */
function loginRequest(string $email, string $password): RedirectResponse
{
    $request = formRequest(StoreTenantLoginRequest::class, [
        'email' => $email,
        'password' => $password,
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
