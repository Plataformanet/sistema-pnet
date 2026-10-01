<?php

namespace App\Http\Controllers;

use App\Services\ApplicantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantApplicantLookupController extends Controller
{
    public function __construct(
        protected ApplicantService $applicantService,
    ) {}

    /**
     * Busca o proponente pelo CPF para pré-preencher o formulário da proposta.
     */
    public function __invoke(Request $request)
    {
        try {
            $cpf = preg_replace('/\D/', '', (string) $request->query('cpf'));

            if (strlen($cpf) !== 11) {
                return response()->json(['message' => 'Informe um CPF com 11 dígitos.'], 422);
            }

            return response()->json(['applicant' => $this->applicantService->lookup($cpf, $request->user(), tenant())]);
        } catch (\Throwable $th) {
            Log::error('Erro ao buscar proponente: '.$th->getMessage());

            return response()->json(['message' => 'Erro ao buscar proponente.'], 500);
        }
    }
}
