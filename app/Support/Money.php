<?php

namespace App\Support;

/**
 * Formatação única de valores monetários guardados em centavos inteiros,
 * usada em PDFs e e-mails (o front usa `formatMoney`, de `@/lib/masks`).
 */
final class Money
{
    public static function format(?int $cents): string
    {
        return 'R$ '.number_format(($cents ?? 0) / 100, 2, ',', '.');
    }

    /**
     * Aplica um percentual (ex.: 3 = 3%) sobre centavos, arredondando só no fim.
     */
    public static function percentOf(int $cents, float|string $rate): int
    {
        return (int) round($cents * (float) $rate / 100);
    }

    /**
     * Converte reais com casas decimais (ex.: retorno de API) para centavos.
     */
    public static function fromReais(float|int|string|null $value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
