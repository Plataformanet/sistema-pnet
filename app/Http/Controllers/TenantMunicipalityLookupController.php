<?php

namespace App\Http\Controllers;

use App\Enums\SupportedState;
use App\Models\ItbiMunicipality;
use App\Services\Ibge\IbgeLocalityClient;
use Illuminate\Support\Facades\Log;

class TenantMunicipalityLookupController extends Controller
{
    public function __construct(
        protected IbgeLocalityClient $ibgeLocalityClient,
    ) {}

    /**
     * Municípios da UF (IBGE) com a indicação de cadastro de ITBI.
     */
    public function __invoke(SupportedState $state)
    {
        try {
            $itbi = tenant()->run(fn () => ItbiMunicipality::where('state', $state->value)
                ->get(['ibge_code', 'module'])
                ->mapWithKeys(fn (ItbiMunicipality $municipality) => [$municipality->ibge_code => $municipality->module->value]));

            return response()->json($this->ibgeLocalityClient->municipalities($state)->map(fn (array $municipality) => [
                'ibge_code' => $municipality['ibge_code'],
                'name' => $municipality['name'],
                'has_itbi' => $itbi->has($municipality['ibge_code']),
                'itbi_module' => $itbi->get($municipality['ibge_code']),
            ])->values());
        } catch (\Throwable $th) {
            Log::error('Erro ao buscar municípios do IBGE: '.$th->getMessage());

            return response()->json(['message' => 'Não foi possível carregar os municípios. Tente novamente.'], 500);
        }
    }
}
