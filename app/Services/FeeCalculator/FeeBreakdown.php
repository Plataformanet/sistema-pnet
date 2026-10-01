<?php

namespace App\Services\FeeCalculator;

use App\Services\RegistryFee\RegistryFeeResult;
use Illuminate\Support\Str;

/**
 * Tabela de resultado montada no servidor (atos, subtotais, taxas extras,
 * ITBI, serviços e totais), consumida igualmente pela tela, pelo PDF e pelo
 * e-mail. Valores em centavos.
 */
final readonly class FeeBreakdown
{
    /**
     * Ordem das colunas (a mesma da calculadora de origem), por prefixo da
     * chave do ato. O jsonb reordena as chaves gravadas, então a ordem não
     * pode vir do resultado. Chaves desconhecidas entram antes do subtotal.
     */
    private const COLUMN_ORDER = ['descricao', 'emolumento', 'estado', 'ipesp', 'registro_civil', 'tribunal_de_justica', 'ministerio_publico'];

    /**
     * @param  array<int, array{name: string, amount: int}>  $services
     */
    public function __construct(
        public RegistryFeeResult $result,
        public ?int $itbi = null,
        public array $services = [],
    ) {}

    /**
     * @param  array<string, mixed>  $apiResult  `result` bruto gravado no cálculo/estimativa
     * @param  array<int, array{name: string, amount: int}>  $services
     */
    public static function fromApiResult(array $apiResult, ?int $itbi, array $services = []): self
    {
        return new self(RegistryFeeResult::fromArray($apiResult), $itbi, $services);
    }

    public function servicesTotal(): int
    {
        return (int) array_sum(array_column($this->services, 'amount'));
    }

    public function grandTotal(): int
    {
        return $this->result->total + ($this->itbi ?? 0) + $this->servicesTotal();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $first = $this->result->acts[0] ?? [];

        $columns = collect($first)
            ->map(fn ($value, string $key) => [
                'key' => $key,
                'label' => Str::ucfirst(str_replace('_', ' ', $key)),
                'numeric' => $key !== 'descricao' && is_int($value),
            ])
            ->sortBy(fn (array $column) => self::columnPosition($column['key']))
            ->values()
            ->all();

        $subtotals = collect($columns)
            ->filter(fn (array $column) => $column['numeric'])
            ->mapWithKeys(fn (array $column) => [
                $column['key'] => (int) collect($this->result->acts)->sum(fn (array $act) => is_int($act[$column['key']] ?? null) ? $act[$column['key']] : 0),
            ])
            ->all();

        return [
            'columns' => $columns,
            'acts' => $this->result->acts,
            'subtotals' => $subtotals,
            'extra_fees' => $this->result->extraFees,
            'calculation_total' => $this->result->total,
            'itbi' => $this->itbi,
            'services' => $this->services,
            'grand_total' => $this->grandTotal(),
            'show_grand_total' => $this->itbi !== null || $this->services !== [],
            'extra_information' => $this->result->extraInformation,
        ];
    }

    private static function columnPosition(string $key): int
    {
        if ($key === 'subtotal') {
            return PHP_INT_MAX;
        }

        foreach (self::COLUMN_ORDER as $position => $prefix) {
            if (str_starts_with($key, $prefix)) {
                return $position;
            }
        }

        return count(self::COLUMN_ORDER);
    }
}
