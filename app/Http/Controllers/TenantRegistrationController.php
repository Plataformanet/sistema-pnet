<?php

namespace App\Http\Controllers;

use App\Enums\TenantProvisioningStatus;
use App\Http\Requests\StoreTenantRegistrationRequest;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantRegistrationController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    public function create()
    {
        return Inertia::render('central/Register');
    }

    public function store(StoreTenantRegistrationRequest $request)
    {
        try {
            $tenant = $this->tenantService->store($request->validated());

            // O banco do tenant é provisionado em background. A página fica
            // aguardando (polling) e redireciona ao login quando ficar pronto.
            return Inertia::render('central/Provisioning', [
                'tenantId' => $tenant->id,
                'loginUrl' => $this->loginUrl($tenant),
                'statusUrl' => route('cadastro.status', $tenant->id),
            ]);
        } catch (\Throwable $th) {
            Log::error('Erro ao criar tenant', ['exception' => $th]);

            return back()
                ->withInput()
                ->withErrors([
                    'cadastro' => 'Não foi possível concluir o cadastro. Tente novamente.',
                ]);
        }
    }

    /**
     * Endpoint consultado via polling pela página de provisionamento.
     */
    public function status(string $tenant): JsonResponse
    {
        $model = Tenant::find($tenant);

        // Se o provisionamento falhou, o tenant é removido — tratamos a ausência
        // como falha para a página encerrar o polling.
        if (! $model) {
            return response()->json([
                'status' => TenantProvisioningStatus::FAILED->value,
                'ready' => false,
            ]);
        }

        return response()->json([
            'status' => $model->provisioningStatus()->value,
            'ready' => $model->isProvisioned(),
        ]);
    }

    protected function loginUrl(Tenant $tenant): string
    {
        $domain = $tenant->domains()->first()->domain;

        // O app do tenant é o mesmo app, em outro host — então herda scheme e
        // porta do request atual (evita hardcode de porta como 8005).
        $scheme = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = in_array($port, [80, 443, null], true) ? '' : ":{$port}";

        return "{$scheme}://{$domain}{$portSuffix}/login";
    }
}
