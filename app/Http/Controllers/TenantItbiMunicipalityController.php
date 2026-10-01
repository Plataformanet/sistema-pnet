<?php

namespace App\Http\Controllers;

use App\Enums\ItbiModule;
use App\Enums\SupportedState;
use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StoreItbiMunicipalityRequest;
use App\Http\Requests\UpdateItbiMunicipalityRequest;
use App\Services\ItbiMunicipalityService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantItbiMunicipalityController extends Controller
{
    public function __construct(
        protected ItbiMunicipalityService $itbiMunicipalityService,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        return Inertia::render('tenant/documents/itbi-municipalities/list/List', [
            'municipalities' => $this->itbiMunicipalityService->findAll($request->validated(), tenant()),
            'filters' => $request->validated(),
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/documents/itbi-municipalities/create/Create', $this->options());
    }

    public function store(StoreItbiMunicipalityRequest $request)
    {
        try {
            $municipality = $this->itbiMunicipalityService->store($request->validated(), tenant());

            return redirect()->route(
                $municipality->module->usesBrackets() ? 'tenant.documents.itbi-municipalities.brackets.edit' : 'tenant.documents.itbi-municipalities.rates.edit',
                $municipality->id,
            )->with('success', 'Município cadastrado com sucesso! Informe agora os parâmetros do ITBI.');
        } catch (\Throwable $th) {
            Log::error('Erro ao cadastrar município de ITBI: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao cadastrar município!');
        }
    }

    public function edit(string $id)
    {
        $municipality = $this->itbiMunicipalityService->findById($id, tenant());

        return Inertia::render('tenant/documents/itbi-municipalities/edit/Edit', array_merge($this->options(), [
            'municipality' => $municipality,
        ]));
    }

    public function update(UpdateItbiMunicipalityRequest $request, string $id)
    {
        try {
            $this->itbiMunicipalityService->update($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.itbi-municipalities.list')->with('success', 'Município atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar município de ITBI: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar município!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->itbiMunicipalityService->delete($id, tenant());

            return redirect()->route('tenant.documents.itbi-municipalities.list')->with('success', 'Município excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir município de ITBI: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir município!');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'states' => SupportedState::options(),
            'modules' => ItbiModule::options(),
        ];
    }
}
