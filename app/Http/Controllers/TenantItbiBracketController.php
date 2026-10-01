<?php

namespace App\Http\Controllers;

use App\Enums\ItbiModule;
use App\Http\Requests\UpdateItbiBracketsRequest;
use App\Services\ItbiMunicipalityService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantItbiBracketController extends Controller
{
    public function __construct(
        protected ItbiMunicipalityService $itbiMunicipalityService,
    ) {}

    public function edit(string $id)
    {
        $municipality = $this->itbiMunicipalityService->findById($id, tenant());

        if ($municipality->module !== ItbiModule::VALUE_BRACKETS) {
            return redirect()->route('tenant.documents.itbi-municipalities.rates.edit', $id);
        }

        return Inertia::render('tenant/documents/itbi-municipalities/brackets/Brackets', [
            'municipality' => $municipality,
        ]);
    }

    public function update(UpdateItbiBracketsRequest $request, string $id)
    {
        try {
            $this->itbiMunicipalityService->updateBrackets($request->validated(), $id, tenant());

            return redirect()->route('tenant.documents.itbi-municipalities.list')->with('success', 'Faixas salvas com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao salvar faixas de ITBI: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao salvar faixas!');
        }
    }
}
