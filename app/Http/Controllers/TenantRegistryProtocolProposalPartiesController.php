<?php

namespace App\Http\Controllers;

use App\Services\RegistryProtocolService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantRegistryProtocolProposalPartiesController extends Controller
{
    public function __construct(
        protected RegistryProtocolService $registryProtocolService,
    ) {}

    /**
     * Comprador e vendedor sugeridos a partir da proposta escolhida.
     */
    public function __invoke(Request $request, string $proposalId)
    {
        try {
            $proposal = $this->registryProtocolService->findProposalForParties($proposalId, $request->user(), tenant());

            return response()->json($this->registryProtocolService->partiesFor($proposal));
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => 'Proposta não encontrada.'], 404);
        } catch (\Throwable $th) {
            Log::error('Erro ao buscar as partes da proposta: '.$th->getMessage());

            return response()->json(['message' => 'Erro ao buscar as partes da proposta.'], 500);
        }
    }
}
