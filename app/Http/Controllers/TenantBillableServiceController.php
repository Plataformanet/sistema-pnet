<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StoreBillableServiceRequest;
use App\Http\Requests\UpdateBillableServiceRequest;
use App\Services\BillableServiceService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantBillableServiceController extends Controller
{
    public function __construct(
        protected BillableServiceService $billableServiceService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        $billableServices = $this->billableServiceService->findAll($request->validated(), tenant());

        return Inertia::render('tenant/documents/billable-services/list/List', [
            'billableServices' => $billableServices,
            'filters' => $request->validated(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/billable-services/create/Create', [
        ]);
    }

    public function edit(string $id)
    {
        $billableService = $this->billableServiceService->findById($id, tenant());

        return Inertia::render('tenant/documents/billable-services/edit/Edit', [
            'billableService' => $billableService,
        ]);
    }

    public function store(StoreBillableServiceRequest $request)
    {
        try {
            $this->billableServiceService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.billable-services.list')->with('success', 'Serviço criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar serviço: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar serviço!');
        }
    }

    public function update(UpdateBillableServiceRequest $request, string $id)
    {
        try {
            $this->billableServiceService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.billable-services.list')->with('success', 'Serviço atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar serviço: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar serviço!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->billableServiceService->delete($id, tenant());

            return redirect()->route('tenant.documents.billable-services.list')->with('success', 'Serviço excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir serviço: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir serviço!');
        }
    }

    public function restore(string $id)
    {
        try {
            $this->billableServiceService->restore($id, tenant());

            return redirect()->route('tenant.documents.billable-services.list')->with('success', 'Serviço restaurado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao restaurar serviço: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao restaurar serviço!');
        }
    }
}
