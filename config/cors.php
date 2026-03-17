<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'], // garanta que sua rota está coberta
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // em dev está ok, em produção restrinja
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // importante para sessão/cookies com Inertia
];
