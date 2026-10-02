<?php

use App\Http\Requests\ResetPasswordRequest;
use App\Mail\PasswordResetLinkMail;
use App\Models\User;
use App\Services\TenantPasswordService;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();
    Mail::fake();

    $this->user = $this->tenant->run(fn () => User::factory()->create([
        'email' => 'cliente@example.com',
        'password' => 'senha-antiga-123',
    ]));
    $this->service = app(TenantPasswordService::class);
});

/**
 * @return array{token: string, email: string}
 */
function tokenFromUrl(string $url): array
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return ['token' => (string) $query['token'], 'email' => (string) $query['email']];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function resetPayload(string $url, array $overrides = []): array
{
    return array_merge(tokenFromUrl($url), [
        'password' => 'Nova-Senha-2026',
        'password_confirmation' => 'Nova-Senha-2026',
    ], $overrides);
}

test('pedir o link grava o token no banco do tenant e envia o e-mail com o link de redefinição', function () {
    $this->service->sendResetLink('cliente@example.com', $this->tenant);

    $this->tenant->run(fn () => expect(DB::table('password_reset_tokens')->where('email', 'cliente@example.com')->exists())->toBeTrue());

    Mail::assertQueued(PasswordResetLinkMail::class, fn (PasswordResetLinkMail $mail) => $mail->hasTo('cliente@example.com')
        && str_contains($mail->resetUrl, 'token=')
        && str_contains($mail->resetUrl, 'email=cliente%40example.com')
        && $mail->afterCommit === true);
});

test('pedir o link para um e-mail sem cadastro não envia nada e não revela que o e-mail não existe', function () {
    $this->service->sendResetLink('ninguem@example.com', $this->tenant);

    Mail::assertNothingQueued();
});

test('redefinir com o token do e-mail troca a senha no banco do tenant', function () {
    $this->service->sendResetLink('cliente@example.com', $this->tenant);
    $mail = Mail::queued(PasswordResetLinkMail::class)->first();

    $reset = $this->service->reset(resetPayload($mail->resetUrl), $this->tenant);

    expect($reset)->toBeTrue();
    $this->tenant->run(fn () => expect(Hash::check('Nova-Senha-2026', $this->user->fresh()->password))->toBeTrue());
});

test('token inválido ou de outro e-mail não troca a senha', function () {
    $this->service->sendResetLink('cliente@example.com', $this->tenant);
    $mail = Mail::queued(PasswordResetLinkMail::class)->first();

    expect($this->service->reset(resetPayload($mail->resetUrl, ['token' => 'token-falso']), $this->tenant))->toBeFalse()
        ->and($this->service->reset(resetPayload($mail->resetUrl, ['email' => 'outro@example.com']), $this->tenant))->toBeFalse();

    $this->tenant->run(fn () => expect(Hash::check('senha-antiga-123', $this->user->fresh()->password))->toBeTrue());
});

test('o link de "esqueci a senha" expira em 60 minutos', function () {
    $this->service->sendResetLink('cliente@example.com', $this->tenant);
    $mail = Mail::queued(PasswordResetLinkMail::class)->first();

    $this->travel(61)->minutes();

    expect($this->service->reset(resetPayload($mail->resetUrl), $this->tenant))->toBeFalse();
});

test('o link de boas-vindas continua valendo depois de 60 minutos e define a senha', function () {
    $url = $this->service->welcomeUrl($this->user, $this->tenant);

    $this->travel(48)->hours();

    expect($this->service->reset(resetPayload($url), $this->tenant))->toBeTrue();
    $this->tenant->run(fn () => expect(Hash::check('Nova-Senha-2026', $this->user->fresh()->password))->toBeTrue());
});

test('o link de boas-vindas expira depois do prazo configurado', function () {
    $url = $this->service->welcomeUrl($this->user, $this->tenant);

    $this->travel((int) config('auth.passwords.welcome.expire') + 1)->minutes();

    expect($this->service->reset(resetPayload($url), $this->tenant))->toBeFalse();
});

test('a nova senha precisa ser confirmada', function () {
    $request = ResetPasswordRequest::create('/', 'POST', [
        'token' => 'abc',
        'email' => 'cliente@example.com',
        'password' => 'Nova-Senha-2026',
        'password_confirmation' => 'outra',
    ]);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    expect(fn () => $request->validateResolved())->toThrow(ValidationException::class);
});

test('as rotas de senha do tenant existem e têm limite de requisições', function (string $name) {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain('POST')
        ->and($route->gatherMiddleware())->toContain('throttle:password-reset');
})->with(['tenant.password.email', 'tenant.password.update']);

test('ao definir a senha por um link, o link pendente do outro tipo deixa de valer', function () {
    $welcomeUrl = $this->service->welcomeUrl($this->user, $this->tenant);
    $this->service->sendResetLink('cliente@example.com', $this->tenant);
    $resetMail = Mail::queued(PasswordResetLinkMail::class)->first();

    expect($this->service->reset(resetPayload($resetMail->resetUrl), $this->tenant))->toBeTrue()
        ->and($this->service->reset(resetPayload($welcomeUrl, ['password' => 'Outra-Senha-2026', 'password_confirmation' => 'Outra-Senha-2026']), $this->tenant))->toBeFalse();

    $this->tenant->run(function () {
        expect(DB::table('password_set_tokens')->where('email', 'cliente@example.com')->exists())->toBeFalse()
            ->and(Hash::check('Nova-Senha-2026', $this->user->fresh()->password))->toBeTrue();
    });
});
