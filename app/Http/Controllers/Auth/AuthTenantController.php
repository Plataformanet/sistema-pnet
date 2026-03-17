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
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){

            return Inertia::location(route('tenant.dashboard'));
        }

    }

    public function logout(Request $request)
    {
         Auth::logout();

         $request->session()->invalidate();

         $request->session()->regenerateToken();

         return Inertia::location(route('tenant.login'));
    }
}
