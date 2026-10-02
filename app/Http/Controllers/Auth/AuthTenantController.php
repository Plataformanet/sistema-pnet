<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Http\Requests\StoreTenantLoginRequest;
use App\Services\TenantPasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthTenantController extends Controller
{
    public function __construct(
        protected TenantPasswordService $tenantPasswordService,
    ) {}

    public function showLoginForm()
    {
        return Inertia::render('tenant/auth/Login');
    }

    public function login(StoreTenantLoginRequest $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {

            $request->session()->regenerate();

            $home = $request->user()->hasOnlyProposalRestrictedRoles()
                ? route('tenant.documents.proposals.list')
                : route('tenant.dashboard');

            return redirect()->intended($home)->with('success', 'Login realizado com sucesso!');
        }

        return back()->withErrors([
            'invalidLogin' => 'As credenciais informadas estão incorretas.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }

    public function showForgotPasswordForm()
    {
        return Inertia::render('tenant/auth/ForgotPassword');
    }

    public function showResetPasswordForm(Request $request)
    {
        return Inertia::render('tenant/auth/ResetPassword', [
            'token' => (string) $request->query('token'),
            'email' => (string) $request->query('email'),
        ]);
    }

    /**
     * A resposta é a mesma com ou sem cadastro do e-mail, para não revelar
     * quem tem conta no tenant.
     */
    public function sendResetLink(SendPasswordResetLinkRequest $request)
    {
        try {
            $this->tenantPasswordService->sendResetLink($request->validated('email'), tenant());

            return redirect()->back()->with('success', 'Se o e-mail estiver cadastrado, você receberá o link de redefinição em instantes.');
        } catch (\Throwable $th) {
            Log::error('Erro ao enviar link de redefinição de senha: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao enviar o link de redefinição de senha!');
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $reset = $this->tenantPasswordService->reset($request->validated(), tenant());
        } catch (\Throwable $th) {
            Log::error('Erro ao redefinir senha: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao redefinir a senha!');
        }

        if (! $reset) {
            throw ValidationException::withMessages([
                'email' => 'Este link de redefinição é inválido ou expirou. Solicite um novo em "Esqueci minha senha".',
            ]);
        }

        return redirect()->route('tenant.login')
            ->with('success', 'Senha definida com sucesso! Faça o login com a nova senha.')
            ->with('email', $request->validated('email'));
    }
}
