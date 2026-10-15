<?php

namespace App\Http\Controllers;

use App\Services\RegistryProtocolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantRegistryProtocolProposalSearchController extends Controller
{
    public function __construct(
        protected RegistryProtocolService $registryProtocolService,
    ) {}

    /**
     * Propostas para o Combobox do protocolo, com busca por nº ou proponente.
     */
    public function __invoke(Request $request)
    {
        try {
            return response()->json($this->registryProtocolService->searchProposals(
                (string) $request->query('search'),
                $request->user(),
                tenant(),
            ));
        } catch (\Throwable $th) {
            Log::error('Erro ao buscar propostas para o protocolo: '.$th->getMessage());

            return response()->json(['message' => 'Erro ao buscar propostas.'], 500);
        }
    }
}
