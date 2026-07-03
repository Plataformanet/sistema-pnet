<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disco de armazenamento do Drive
    |--------------------------------------------------------------------------
    |
    | Disco (config/filesystems.php) usado para guardar os documentos do módulo
    | de Drive. Em produção usamos o MinIO/S3, isolado por tenant através do
    | FilesystemTenancyBootstrapper. Nos testes, sobrescreva para um disco
    | fake (ex.: 'public').
    |
    */

    'disk' => env('DRIVE_DISK', 'minio'),

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

    'signed_urls' => (bool) env('DRIVE_SIGNED_URLS', false),

    'url_ttl' => (int) env('DRIVE_URL_TTL', 10), // minutos

];
