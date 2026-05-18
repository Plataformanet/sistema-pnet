<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Services\CategoryServicesService;
use App\Services\ServicesService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantServiceController extends Controller
{
    public function __construct(
        protected ServicesService $servicesService,
        protected CategoryServicesService $categoryServicesService
    ) {
    }

    public function index()
    {
        $services = $this->servicesService->findAll(tenant());

        return Inertia::render('tenant/services/services/list/List', [
            'services' => $services
        ]);
    }

    public function create()
    {
        $categories = $this->categoryServicesService->findAll(tenant());

        return Inertia::render('tenant/services/services/create/Create', [
            'categories' => $categories->toArray(),
        ]);
    }

    public function store(StoreServiceRequest $request)
    {
        try {
            $this->servicesService->store($request->validated(), tenant());

            return redirect()->route('tenant.services.services.list')->with('success', 'Serviço criado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar serviço: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao criar serviço!');
        }
    }

    public function edit(string $id)
    {
        $categories = $this->categoryServicesService->findAll(tenant());

        $service = $this->servicesService->findById($id, tenant());

        return Inertia::render('tenant/services/services/edit/Edit', [
            'service'    => $service,
            'categories' => $categories->toArray(),
        ]);
    }

    public function update(UpdateServiceRequest $request, string $id)
    {
        try {
            $this->servicesService->update($id, $request->validated(), tenant());

            return redirect()->route('tenant.services.services.list')->with('success', 'Serviço atualizado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar serviço: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao atualizar serviço!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->servicesService->delete($id, tenant());

            return redirect()->route('tenant.services.services.list')->with('success', 'Serviço excluído com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao excluir serviço: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao excluir serviço!');
        }
    }
}
