<?php

namespace App\Http\Controllers;

use App\Services\TenantRegistrationService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTenantRegistrationRequest;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class TenantRegistrationController extends Controller
{
    public function __construct(protected TenantRegistrationService $tenantRegistrationService){}

    public function create()
    {
      return Inertia::render('Auth/Register');
    }

    public function store(StoreTenantRegistrationRequest $request)
    {
        $tenant = $this->tenantRegistrationService->store($request->validated());

        if(config('app.env') === 'local'){
            $loginUrl = "http://{$tenant->domains()->first()->domain}:8005/login";
        }else{
            $loginUrl = "https://{$tenant->domains()->first()->domain}/login";
        }

        Session::flash('success', 'Conta criada! Faça login para continuar.');
        // Session::flash('email', $tenant->email);

        return Inertia::location($loginUrl);
    }
}
