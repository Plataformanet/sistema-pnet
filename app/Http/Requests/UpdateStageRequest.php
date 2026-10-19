<?php

namespace App\Http\Requests;

use App\Models\Stage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class UpdateStageRequest extends StoreStageRequest
{
    /**
     * O banco não entra nas regras: fica fora do `validated()` e a etapa
     * nunca muda de timeline.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), 'bank_id');
    }

    protected function ignoreId(): ?string
    {
        return $this->route('id');
    }

    /**
     * A ordem é validada na timeline gravada da etapa, não na do input. A
     * regra de unicidade lê o valor duas vezes; `once` evita a segunda consulta.
     */
    protected function timelineBankId(): ?int
    {
        return once(fn () => Stage::find($this->route('id'))?->bank_id);
    }
}
