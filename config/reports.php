<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Download em lote (ZIP) do relatório de propostas finalizadas
    |--------------------------------------------------------------------------
    |
    | Até `zip_sync_limit` documentos o ZIP é gerado na própria requisição.
    | Acima disso, e até `zip_max_files`, ele é gerado na fila e o usuário
    | recebe por e-mail um link assinado, válido por `zip_link_ttl_hours`.
    | Acima de `zip_max_files` a seleção é recusada.
    |
    */

    'zip_sync_limit' => (int) env('REPORTS_ZIP_SYNC_LIMIT', 50),

    'zip_max_files' => (int) env('REPORTS_ZIP_MAX_FILES', 500),

    'zip_link_ttl_hours' => (int) env('REPORTS_ZIP_LINK_TTL_HOURS', 24),

];
