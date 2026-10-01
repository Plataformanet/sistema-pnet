<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tipo de contrato padrão da proposta gerada a partir de orçamento
    |--------------------------------------------------------------------------
    |
    | A conversão de orçamento em proposta não conhece o contrato. Usa este id
    | (padrão: 4, "AQUISIÇÃO À VISTA COM FGTS", que não exige dados de
    | financiamento); se ele não existir, usa o primeiro contrato ativo.
    |
    */

    'default_contract_type_id' => (int) env('PROPOSAL_DEFAULT_CONTRACT_TYPE_ID', 4),

];
