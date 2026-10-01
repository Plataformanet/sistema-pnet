<?php

namespace App\Rules;

use Illuminate\Validation\Validator;

/**
 * Faixas de ITBI (limites inclusivos) não podem se sobrepor: um valor deve
 * cair em uma única faixa. Usada no `after()` do Form Request.
 */
class NonOverlappingBrackets
{
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $brackets = collect($validator->getData()['brackets'] ?? [])
            ->map(fn (array $bracket, int $index) => [
                'index' => $index,
                'min' => (int) $bracket['min_value'],
                'max' => (int) $bracket['max_value'],
            ])
            ->sortBy('min')
            ->values();

        foreach ($brackets as $position => $bracket) {
            if ($bracket['min'] >= $bracket['max']) {
                $validator->errors()->add("brackets.{$bracket['index']}.max_value", 'O valor máximo deve ser maior que o mínimo.');
            }

            $next = $brackets->get($position + 1);

            if ($next !== null && $next['min'] <= $bracket['max']) {
                $validator->errors()->add("brackets.{$next['index']}.min_value", 'Esta faixa se sobrepõe a outra faixa cadastrada.');
            }
        }
    }
}
