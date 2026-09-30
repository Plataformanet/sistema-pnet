<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

class UpdateBankAccountRequest extends StoreBankAccountRequest
{
    /**
     * O saldo inicial só é definido na criação da conta: alterá-lo depois
     * dessincronizaria o saldo atual das movimentações já lançadas. Sem regra,
     * o campo é descartado de `validated()` mesmo que o formulário o envie.
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['initial_balance']);
    }
}
