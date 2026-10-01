<x-mail::message>
Olá {{ $name }},

Uma nova proposta (**Nº {{ $proposalNumber }}**) foi cadastrada em seu nome.

Acesse o sistema com seu usuário e senha para acompanhar o andamento.

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
