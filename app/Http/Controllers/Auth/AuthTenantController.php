<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthTenantController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('tenant/auth/Login');
    }

    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {

            $request->session()->regenerate();

            return redirect()->route('tenant.dashboard');
        }
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

    public function showResetPasswordForm()
    {
        return Inertia::render('tenant/auth/ResetPassword');
    }
}
