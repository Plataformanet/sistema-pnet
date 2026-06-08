<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UpdateInstallmentException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?? 'Erro ao atualizar parcela.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
