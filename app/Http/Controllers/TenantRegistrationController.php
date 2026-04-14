<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRegistrationRequest;
use App\Services\TenantService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class TenantRegistrationController extends Controller
{
    public function __construct(protected TenantService $tenantService)
    {
    }

    public function create()
    {
        return Inertia::render('central/Register');
    }

    public function store(StoreTenantRegistrationRequest $request)
    {
        try {
            $tenant = $this->tenantService->store($request->validated());

            if (config('app.env') === 'local') {
                $loginUrl = "http://{$tenant->domains()->first()->domain}:8005/login";
            } else {
                $loginUrl = "https://{$tenant->domains()->first()->domain}/login";
            }

            Session::flash('success', 'Conta criada! Faça login para continuar.');

            return Inertia::location($loginUrl);

        } catch (\Throwable $th) {
            Log::error('Error creating user for tenant: ' . $th->getMessage());

            Session::flash('error', 'Erro ao criar conta. Por favor, tente novamente.');

            return Inertia::render('central/Register');
        }
    }
}
