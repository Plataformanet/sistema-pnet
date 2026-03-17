<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Se a rota 'tenant.login' existe no contexto atual, usa ela
        if (app('router')->has('tenant.login')) {
            return route('tenant.login');
        }

        // Fallback para o login central
        return route('login');
    }
}
