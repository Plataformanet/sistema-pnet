<?php

namespace App\Services;

use App\Mail\PasswordResetLinkMail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker as Broker;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Definição e redefinição de senha dos usuários do tenant.
 *
 * As rotas de senha do Fortify rodam sem tenancy (procuram o usuário no banco
 * central), então o fluxo do tenant fica aqui, sempre dentro de
 * `$tenant->run()`. Há dois brokers (config/auth.php):
 * - `users`: link de "esqueci a senha", válido por 60 minutos;
 * - `welcome`: link de boas-vindas, com validade maior e tabela própria.
 */
class TenantPasswordService
{
    public const RESET_BROKER = 'users';

    public const WELCOME_BROKER = 'welcome';

    /**
     * Envia o link de redefinição. E-mail sem cadastro não gera erro nem
     * resposta diferente, para não revelar quem tem conta no tenant.
     */
    public function sendResetLink(string $email, Tenant $tenant): void
    {
        $tenant->run(function () use ($email) {
            $this->broker(self::RESET_BROKER)->sendResetLink(
                ['email' => $email],
                fn (User $user, string $token) => Mail::to($user->email)->queue(new PasswordResetLinkMail(
                    $user->name,
                    $this->resetUrl($user, $token),
                    (int) config('auth.passwords.'.self::RESET_BROKER.'.expire'),
                )),
            );
        });
    }

    /**
     * Link de definição de senha do e-mail de boas-vindas. Montado na
     * requisição porque a fila não conhece o domínio do tenant.
     */
    public function welcomeUrl(User $user, Tenant $tenant): string
    {
        return $tenant->run(fn () => $this->resetUrl($user, $this->broker(self::WELCOME_BROKER)->createToken($user)));
    }

    /**
     * Troca a senha se o token for válido em algum dos brokers. Cada broker
     * confere o token na própria tabela e com a própria validade. Depois da
     * troca, os tokens pendentes dos dois brokers são apagados: um link antigo
     * (ex.: o de boas-vindas) não pode mais definir outra senha.
     *
     * @param  array{token: string, email: string, password: string, password_confirmation?: string}  $data
     */
    public function reset(array $data, Tenant $tenant): bool
    {
        return $tenant->run(function () use ($data) {
            $credentials = [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'] ?? $data['password'],
                'token' => $data['token'],
            ];

            $brokers = [self::RESET_BROKER, self::WELCOME_BROKER];
            $resetUser = null;

            foreach ($brokers as $name) {
                $status = $this->broker($name)->reset($credentials, function (User $user, string $password) use (&$resetUser) {
                    $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
                    $user->save();
                    $resetUser = $user;

                    event(new PasswordReset($user));
                });

                if ($status === PasswordBroker::PASSWORD_RESET) {
                    foreach ($brokers as $pending) {
                        $this->broker($pending)->deleteToken($resetUser);
                    }

                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Broker novo a cada uso: o `PasswordBrokerManager` do container guarda a
     * conexão de banco de quando o broker foi criado, e num processo que
     * atende mais de um tenant (worker da fila) gravaria o token no banco
     * errado.
     */
    private function broker(string $name): Broker
    {
        /** @var Broker $broker */
        $broker = (new PasswordBrokerManager(app()))->broker($name);

        return $broker;
    }

    private function resetUrl(User $user, string $token): string
    {
        return route('tenant.reset-password', ['token' => $token, 'email' => $user->email]);
    }
}
