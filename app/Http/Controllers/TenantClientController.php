<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
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
        return Inertia::render('tenant/registrations/clients/list/List');
    }

    public function create()
    {
        return Inertia::render('tenant/registrations/clients/create/Create');
    }

    public function store(StoreContactRequest $request)
    {
        dd($request->validated());
        $tenant = tenant();

        try {
            $contact = $this->contactService->store($request->validated(), $tenant);

            $this->clientService->store($contact, $request->validated(), $tenant);

            return redirect()->route('tenant.registrations.clients.list')->with('success', 'Cliente criado com sucesso!');

        } catch (\Throwable $th) {
            Log::error('Erro ao criar cliente: ' . $th->getMessage());
            $contact->delete();
            return redirect()->back()->with('error', 'Erro ao criar cliente!');
        }
    }

    public function show($id)
    {
        // Mock client
        $client = [
            'id'    => $id,
            'type'  => 'PF',
            'name'  => 'Mock Client',
            'email' => 'mock@client.com',
            'cpf'   => '000.000.000-00',
            'phone' => '(00) 0000-0000',
        ];

        return Inertia::render('tenant/registrations/clients/show/Show', [
            'client' => $client
        ]);
    }

    public function edit($id)
    {
        // Mock client
        $client = [
            'id'    => $id,
            'type'  => 'PF',
            'name'  => 'Mock Client',
            'email' => 'mock@client.com',
            'cpf'   => '000.000.000-00',
            'phone' => '(00) 0000-0000',
        ];

        return Inertia::render('tenant/registrations/clients/edit/Edit', [
            'client' => $client
        ]);
    }

    public function update(Request $request, $id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.clients.list');
    }

    public function destroy($id)
    {
        // Placeholder
        return redirect()->route('tenant.registrations.clients.list');
    }
}
