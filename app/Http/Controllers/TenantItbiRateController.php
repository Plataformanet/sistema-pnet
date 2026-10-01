<?php

namespace App\Http\Controllers;

use App\Enums\ItbiModule;
use App\Http\Requests\UpdateItbiRateRequest;
use App\Services\ItbiMunicipalityService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantItbiRateController extends Controller
{
    public function __construct(
        protected ItbiMunicipalityService $itbiMunicipalityService,
    ) {}

    public function edit(string $id)
    {
        $municipality = $this->itbiMunicipalityService->findById($id, tenant());

        if ($municipality->module === ItbiModule::VALUE_BRACKETS) {
            return redirect()->route('tenant.documents.itbi-municipalities.brackets.edit', $id);
        }

        return Inertia::render('tenant/documents/itbi-municipalities/rate/Rate', [
            'municipality' => $municipality,
            'moduleLabel' => $municipality->module->label(),
            'fields' => $municipality->module->requiredFields(),
        ]);
    }

    public function update(UpdateItbiRateRequest $request, string $id)
    {
        try {
            $this->itbiMunicipalityService->updateRate($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.itbi-municipalities.list')->with('success', 'Alíquotas salvas com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao salvar alíquotas de ITBI: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao salvar alíquotas!');
        }
    }
}
