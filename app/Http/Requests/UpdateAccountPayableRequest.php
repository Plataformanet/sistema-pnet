<?php

namespace App\Http\Requests;

class UpdateAccountPayableRequest extends StoreAccountPayableRequest
{
    public function rules(): array
    {
        return parent::rules();
    }

    /**
     * Na edição, as parcelas enviadas podem ser as antigas quando o total muda
     * (o service recria as abertas). A consistência da soma é verificada pelo
     * service, que conhece as parcelas já pagas.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [];
    }
}
