<?php

/*
|--------------------------------------------------------------------------
| Textos fixos da Calculadora de Emolumentos e do Orçamento
|--------------------------------------------------------------------------
|
| PRD 03-calculadora-emolumentos, seção 13. Os textos legais integrais da
| origem (descontos e averbação) devem substituir as versões resumidas abaixo
| quando estiverem disponíveis.
|
*/

return [
    'presentation' => 'A Calculadora de Emolumentos estima os custos do registro do imóvel de forma rápida, eficaz e gratuita. Desta forma não é necessário se deslocar até o cartório para realizar a previsão do preço do registro do imóvel. Caso o negócio jurídico envolva mais de um imóvel, deve ser realizado um cálculo separado para cada um dos imóveis. O valor definitivo será calculado pelo respectivo Registro de Imóveis após o protocolo.',

    'common_notes' => 'Deve ser utilizado no cálculo o maior valor entre o valor declarado pelas partes e o da avaliação fiscal para fins do imposto de transmissão (ITBI ou ITCMD). Caso o negócio jurídico envolva mais de um imóvel, deve ser realizado um cálculo separado para cada um dos imóveis. A Calculadora de Emolumentos tem por objetivo fornecer uma estimativa dos valores previstos em lei para o registro pretendido. O valor definitivo será calculado pelo respectivo Registro de Imóveis após o protocolo.',

    'types' => [
        1 => 'Utilize essa ferramenta para cálculo de registros de compra e venda, promessa de compra e venda, doação, usucapião, inventário, arrematação, dação em pagamento, integralização ao capital de sociedade, permuta, entre outros.
        Em todos os casos, deve ser utilizado no cálculo o maior valor entre o valor declarado pelas partes e o da avaliação fiscal para fins do imposto de transmissão (ITBI ou ITCMD).
        Caso o negócio jurídico envolva mais de um imóvel, deve ser realizado um cálculo separado para cada um dos imóveis.
        Essa é uma ferramenta em construção. Tem por objetivo fornecer uma estimativa dos valores previstos em lei para o registro pretendido. O valor definitivo será calculado pelo respectivo Registro de Imóveis após o protocolo.',
        2 => 'Utilize essa ferramenta para cálculo de compra e venda financiada pelo sistema financeiro. Para a avaliação do imóvel, deve ser utilizado no cálculo o maior valor entre o valor total de venda declarado pelas partes e o da avaliação fiscal para fins do ITBI. No valor do financiamento deve ser informado o valor total da dívida.',
        3 => 'Utilize essa ferramenta para cálculo de averbações com valor econômico, conforme o Código de Normas da Corregedoria, Art. 1273.',
    ],

    'discounts' => [
        'SFH' => 'Lei 6.015/73, Art. 290 — redução de 50% dos emolumentos na primeira aquisição residencial financiada pelo SFH. Lei 3.350/99 RJ, Art. 44 — isenção do acréscimo de 20% e das taxas das Leis 489/1981 e 590/1987 na primeira aquisição da casa própria ou com interveniência de Cooperativas Habitacionais. § 3º O notário ou registrador exigirá certidões dos Ofícios de Distribuição competentes.',
        'EP' => 'Lei 3.350/99 RJ, Art. 44 — isenção do acréscimo de 20% e das taxas das Leis 489/1981 e 590/1987 na primeira aquisição da casa própria ou com interveniência de Cooperativas Habitacionais. § 3º O notário ou registrador exigirá certidões dos Ofícios de Distribuição competentes.',
        'PCVA_MCMV' => 'Lei 11.977/09, Art. 42, II — redução de 50% para os atos relacionados aos demais empreendimentos do PMCMV.',
        'FAR_FDS' => 'Lei 11.977/09, Art. 42, I — redução de 75% para os empreendimentos do FAR e do FDS.',
        'HAP' => 'Lei 2.751/2002 — redução de metade das custas para habitação popular, da aquisição do terreno à averbação/registro da construção.',
    ],

    'legal_notice' => '*OS VALORES APRESENTADOS ESTÃO SUSCETÍVEIS A ALTERAÇÕES DEPENDENDO DA ATRIBUIÇÃO DA VALORAÇÃO JUNTO À PREFEITURA COMPETENTE',

    'messages' => [
        'fee_estimate_attached' => 'Emolumento registrado com sucesso!',
        'quote_created' => 'Orçamento criado com sucesso!',
        'quote_updated' => 'Orçamento atualizado com sucesso!',
        'quote_deleted' => 'Orçamento excluído com sucesso!',
        'email_sent' => 'Email enviado com sucesso!',
        'proposal_created' => 'Proposta criada com sucesso!',
        'api_unavailable' => 'Ocorreu um erro ao processar sua solicitação, servidor da calculadora está fora do ar. Por favor, tente novamente mais tarde.',
        'municipality_not_configured' => 'Município não encontrado, por favor verifique em ITBI Municípios se foi cadastrado de forma correta!',
        'out_of_brackets' => 'Valor do imóvel fora das faixas cadastradas para :municipality.',
    ],
];
