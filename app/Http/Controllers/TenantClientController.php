<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Services\ClientService;
use App\Services\ContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantClientController extends Controller
{
    public function __construct(
        protected ContactService $contactService,
        protected ClientService $clientService,
    ) {
    }

    public function index()
    {
        $clients = $this->clientService->findAll();

        return Inertia::render('tenant/registrations/clients/list/List', [
            'clients' => $clients,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/registrations/clients/create/Create');
    }

    public function store(StoreContactRequest $request)
    {
        $tenant = tenant();

        try {
            $contact = $this->contactService->store($request->validated(), $tenant);

            $this->clientService->store($contact, $request->validated(), $tenant);

            return redirect()->route('tenant.registrations.clients.list')->with('success', 'Cliente criado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar cliente: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao criar cliente!');
        }
    }

    public function show($id)
    {
        $client = $this->clientService->findById($id);

        return Inertia::render('tenant/registrations/clients/show/Show', [
            'client' => $client
        ]);
    }

    public function edit($id)
    {
        $client = $this->clientService->findById($id);

        return Inertia::render('tenant/registrations/clients/edit/Edit', [
            'client' => $client->toArray()
        ]);
    }

    public function update(UpdateContactRequest $request, $id)
    {
        $tenant = tenant();

        try {
            $contact = $this->contactService->update($request->validated(), $tenant, $id);

            $this->clientService->update($contact, $request->validated(), $tenant);

            return redirect()->route('tenant.registrations.clients.list')->with('success', 'Cliente atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar cliente: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao atualizar cliente!');
        }
    }

    public function destroy($id)
    {
        $tenant = tenant();

        try {
            $this->contactService->destroy($tenant, $id);

            return redirect()->route('tenant.registrations.clients.list')->with('success', 'Cliente excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir cliente: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Erro ao excluir cliente!');
        }
    }
}
