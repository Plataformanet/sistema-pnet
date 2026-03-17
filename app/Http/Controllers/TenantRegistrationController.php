<?php

namespace App\Http\Controllers;

use App\Services\TenantRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class TenantRegistrationController extends Controller
{
    public function __construct(protected TenantRegistrationService $tenantRegistrationService){}

    public function create(){
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        $tenant = $this->tenantRegistrationService->store($request->all());

        $loginUrl = "http://{$tenant->domains()->first()->domain}:8005/login";

        Session::flash('success', 'Conta criada! Faça login para continuar.');
        // Session::flash('email', $tenant->email);

        return Inertia::location($loginUrl);
    }
}
