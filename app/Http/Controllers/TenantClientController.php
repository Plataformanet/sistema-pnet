<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Contact;
use App\Services\ClientService;
use App\Services\ContactService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantClientController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
        protected ClientService $clientService,
    ) {}

    public function index()
    {
        $clients = $this->clientService->findAll(tenant());

        return Inertia::render('tenant/registrations/clients/list/List', [
            'clients' => $clients->toArray(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/registrations/clients/create/Create');
    }

    public function store(StoreClientRequest $request)
    {
        try {

            $contact = Contact::where('cpf_cnpj', $request->validated('cpf_cnpj'))->first();

            if (! $contact) {
                $contact = $this->contactService->store($request->validated(), tenant());
            }

            $this->clientService->store($contact, $request->validated(), tenant());

            return redirect()->route('tenant.registrations.clients.list')->with('success', 'Cliente criado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar cliente: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar cliente!');
        }
    }

    public function show(string $id)
    {
        $client = $this->clientService->findById($id, tenant());

        return Inertia::render('tenant/registrations/clients/show/Show', [
            'client' => $client,
        ]);
    }

    public function edit(string $id)
    {
        $client = $this->clientService->findById($id, tenant());

        return Inertia::render('tenant/registrations/clients/edit/Edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, string $id)
    {
        try {
            $contact = $this->contactService->update($request->validated(), tenant(), $id);

            $this->clientService->update($contact, $request->validated(), tenant());

            return redirect()->route('tenant.registrations.clients.list')->with('success', 'Cliente atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar cliente: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar cliente!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->contactService->destroy(tenant(), $id);

            return redirect()->route('tenant.registrations.clients.list')->with('success', 'Cliente excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir cliente: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir cliente!');
        }
    }

    public function getContactByCpfCnpj(string $cpfCnpj)
    {
        try {
            $contact = $this->contactService->getContactByCpfCnpj($cpfCnpj, tenant());

            return response()->json($contact);
        } catch (\Throwable $th) {
            Log::error('Erro ao buscar contato: '.$th->getMessage());

            return response()->json([
                'message' => 'Erro ao buscar contato!',
            ], 500);
        }
    }
}
