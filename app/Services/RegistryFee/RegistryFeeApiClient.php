<?php

namespace App\Services\RegistryFee;

use App\Exceptions\RegistryFee\RegistryFeeRejectedException;
use App\Exceptions\RegistryFee\RegistryFeeUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente da API de emolumentos do Registro de Imóveis. TLS ligado, token só
 * via configuração e retentativa apenas em falha de conexão (nunca em 4xx).
 */
class RegistryFeeApiClient
{
    /**
     * Devolve o `result` bruto da API (gravado para auditoria) e o DTO em centavos.
     *
     * @return array{raw: array<string, mixed>, result: RegistryFeeResult}
     *
     * @throws RegistryFeeRejectedException
     * @throws RegistryFeeUnavailableException
     */
    public function calculate(RegistryFeeRequestData $data): array
    {
        $context = [
            'state' => $data->state->value,
            'municipality' => $data->municipalityIbgeCode,
            'type' => $data->type->value,
        ];

        try {
            $response = Http::baseUrl((string) config('services.registry_fee_calculator.url'))
                ->withToken((string) config('services.registry_fee_calculator.token'))
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout((int) config('services.registry_fee_calculator.timeout'))
                ->retry([200, 1000], throw: false, when: fn (Throwable $exception) => $exception instanceof ConnectionException)
                ->post('calculate', $data->toPayload());
        } catch (ConnectionException $exception) {
            report(new RegistryFeeUnavailableException('Falha de conexão com a API de emolumentos.', previous: $exception));

            throw new RegistryFeeUnavailableException('Falha de conexão com a API de emolumentos.', previous: $exception);
        }

        // Autenticação, limite e erro do servidor são indisponibilidade, mesmo
        // que o corpo traga uma mensagem: ela não é para o usuário final.
        $unavailable = $response->serverError() || in_array($response->status(), [401, 403, 404, 429], true);
        $message = $response->json('errorMessage') ?? $response->json('message');

        if (! $unavailable && is_string($message) && $message !== '' && ! is_array($response->json('result'))) {
            throw new RegistryFeeRejectedException($message);
        }

        if ($unavailable || $response->failed() || ! is_array($response->json('result.atos'))) {
            $exception = new RegistryFeeUnavailableException('Resposta inválida da API de emolumentos (HTTP '.$response->status().').');
            report($exception);
            logger()->warning('API de emolumentos indisponível', $context + ['status' => $response->status()]);

            throw $exception;
        }

        $raw = (array) $response->json('result');
        $result = RegistryFeeResult::fromArray($raw);
        $raw['extra_information'] = $result->extraInformation;

        return ['raw' => $raw, 'result' => $result];
    }
}
