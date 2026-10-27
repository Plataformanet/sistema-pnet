<?php

namespace App\Http\Controllers;

use App\Enums\RegistryProtocolPositionEnum;
use App\Http\Requests\IndexRegistryProtocolRequest;
use App\Http\Requests\StoreRegistryProtocolRequest;
use App\Http\Requests\UpdateRegistryProtocolRequest;
use App\Services\RegistryProtocolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantRegistryProtocolController extends Controller
{
    public function __construct(
        protected RegistryProtocolService $registryProtocolService,
    ) {}

    public function index(IndexRegistryProtocolRequest $request)
    {
        $filters = $request->validated();

        return Inertia::render('tenant/documents/registry-protocols/list/List', [
            'protocols' => $this->registryProtocolService->paginate($filters, tenant()),
            'tabCounts' => $this->registryProtocolService->tabCounts($filters, tenant()),
            'positions' => RegistryProtocolPositionEnum::options(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('tenant/documents/registry-protocols/create/Create', [
            'positions' => RegistryProtocolPositionEnum::options(),
            'proposal' => $this->registryProtocolService->proposalOption(
                filter_var($request->query('proposal_id'), FILTER_VALIDATE_INT) ?: null,
                $request->user(),
                tenant(),
            ),
        ]);
    }

    public function edit(string $id)
    {
        return Inertia::render('tenant/documents/registry-protocols/edit/Edit', [
            'protocol' => $this->registryProtocolService->findForEdit($id, tenant()),
            'positions' => RegistryProtocolPositionEnum::options(),
        ]);
    }

    public function store(StoreRegistryProtocolRequest $request)
    {
        try {
            $this->registryProtocolService->store($request->validated(), tenant());

            return redirect()->route('tenant.documents.registry-protocols.list')->with('success', 'Protocolo de registro criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar protocolo de registro: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar protocolo de registro!');
        }
    }

    public function update(UpdateRegistryProtocolRequest $request, string $id)
    {
        try {
            $this->registryProtocolService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.registry-protocols.list')->with('success', 'Protocolo de registro atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar protocolo de registro: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar protocolo de registro!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->registryProtocolService->delete($id, tenant());

            return redirect()->route('tenant.documents.registry-protocols.list')->with('success', 'Protocolo de registro excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir protocolo de registro: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir protocolo de registro!');
        }
    }
}
