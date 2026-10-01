<?php

namespace App\Http\Requests;

use App\Models\Quote;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdateQuoteRequest extends StoreQuoteRequest
{
    /**
     * O banco já gravado no orçamento continua válido mesmo se foi excluído.
     */
    protected function bankRule(): Exists
    {
        $current = Quote::whereKey($this->route('id'))->value('bank_id');

        return Rule::exists('banks', 'id')->where(fn ($query) => $query->where(
            fn ($query) => $query->whereNull('deleted_at')->when($current, fn ($query) => $query->orWhere('id', $current))
        ));
    }
}
