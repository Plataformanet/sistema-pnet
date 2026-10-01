<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disco de armazenamento (bucket)
    |--------------------------------------------------------------------------
    |
    | Disco (config/filesystems.php) usado pelos módulos que guardam arquivos.
    | Em produção usamos o MinIO/S3, isolado por tenant através do
    | FilesystemTenancyBootstrapper. Nos testes, sobrescreva para um disco
    | fake (ex.: 'public'). A subpasta de cada módulo (drive, documentacoes,
    | etc.) é definida por uma constante no service do próprio módulo.
    |
    */

    'disk' => env('BUCKET_DISK', 'minio'),

    /*
    |--------------------------------------------------------------------------
    | Downloads via URL assinada
    |--------------------------------------------------------------------------
    |
    | Quando habilitado (e o disco suportar), o download redireciona para uma
    | URL temporária assinada, tirando o tráfego do servidor da aplicação.
    | Mantenha DESLIGADO no Sail/dev: o MinIO em Docker assina a URL com o
    | endpoint interno (minio:9000), inacessível pelo navegador. Nesse caso o
    | arquivo é transmitido (stream) pela própria aplicação, que funciona
    | em qualquer ambiente. Ligue apenas quando o endpoint for público.
    |
    */

    'signed_urls' => (bool) env('BUCKET_SIGNED_URLS', false),

    'url_ttl' => (int) env('BUCKET_URL_TTL', 10), // minutos

    /*
    |--------------------------------------------------------------------------
    | Limite de upload dos documentos da proposta
    |--------------------------------------------------------------------------
    |
    | Tamanho máximo, em KB, de cada documento enviado nas propostas (RG,
    | comprovantes, boletos). O legado anunciava 3 MB mas validava 300 MB.
    |
    */

    'proposal_document_max_kb' => (int) env('PROPOSAL_DOCUMENT_MAX_KB', 20480),

];
