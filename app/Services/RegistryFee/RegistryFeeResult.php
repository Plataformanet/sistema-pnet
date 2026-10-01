<?php

namespace App\Services\RegistryFee;

use App\Support\Money;

/**
 * Resultado da API de emolumentos com todos os valores já em centavos. A
 * conversão de reais para centavos acontece só aqui.
 */
final readonly class RegistryFeeResult
{
    /**
     * HTML aceito em `extra_information` (sem atributos).
     */
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><ul><ol><li>';

    /**
     * @param  array<int, array<string, int|string|null>>  $acts
     * @param  array<int, array{description: string, amount: int}>  $extraFees
     */
    public function __construct(
        public array $acts,
        public array $extraFees,
        public int $total,
        public ?string $extraInformation,
    ) {}

    /**
     * @param  array<string, mixed>  $result
     */
    public static function fromArray(array $result): self
    {
        $acts = collect($result['atos'] ?? [])
            ->map(fn (array $act) => collect($act)
                ->map(fn ($value, string $key) => $key !== 'descricao' && is_numeric($value) ? Money::fromReais($value) : $value)
                ->all())
            ->values()
            ->all();

        $extraFees = collect($result['taxas_extras'] ?? [])
            ->map(fn (array $fee) => [
                'description' => (string) ($fee['descricao'] ?? ''),
                'amount' => Money::fromReais($fee['valor'] ?? 0),
            ])
            ->values()
            ->all();

        return new self(
            $acts,
            $extraFees,
            Money::fromReais($result['total'] ?? 0),
            self::sanitize($result['extra_information'] ?? null),
        );
    }

    /**
     * `extra_information` é HTML de terceiro: só tags de formatação, sem
     * atributos (evita XSS ao renderizar com v-html).
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);

        return preg_replace('/<(\/?)(\w+)[^>]*>/', '<$1$2>', $clean);
    }
}
