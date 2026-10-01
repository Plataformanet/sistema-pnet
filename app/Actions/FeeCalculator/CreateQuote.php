<?php

namespace App\Actions\FeeCalculator;

use App\Models\FeeCalculation;
use App\Models\FeeEstimate;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillableServiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cria o orçamento a partir de um cálculo: dados do cliente, estimativa com
 * validade e serviços com o valor efetivo (negociado ou de tabela).
 */
class CreateQuote
{
    public function __construct(
        protected BillableServiceService $billableServiceService,
    ) {}

    /**
     * @param  array<string, mixed>  $data  dados validados por StoreQuoteRequest
     */
    public function handle(array $data, FeeCalculation $calculation, User $user, Tenant $tenant): Quote
    {
        return $tenant->run(function () use ($data, $calculation, $user, $tenant) {
            return DB::transaction(function () use ($data, $calculation, $user, $tenant) {
                $quote = Quote::create(array_merge(self::clientAttributes($data), ['created_by' => $user->id]));

                $quote->feeEstimate()->create(array_merge(FeeEstimate::attributesFrom($calculation), [
                    'valid_until' => $data['valid_until'],
                ]));

                $quote->services()->sync(
                    $this->billableServiceService->resolveSelection($data['services'] ?? [], $tenant)
                        ->mapWithKeys(fn (array $item) => [$item['service']->id => ['amount' => $item['amount']]])
                        ->all()
                );

                return $quote;
            });
        });
    }

    /**
     * Normaliza os dados do cliente: nome em Title Case, CPF só com dígitos e
     * e-mail em minúsculas.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function clientAttributes(array $data): array
    {
        return [
            'name' => Str::title(Str::squish((string) $data['name'])),
            'cpf' => preg_replace('/\D/', '', (string) $data['cpf']),
            'email' => Str::lower(trim((string) $data['email'])),
            'phone' => $data['phone'],
            'profession' => $data['profession'],
            'marital_status' => $data['marital_status'],
            'bank_id' => $data['bank_id'],
        ];
    }
}
