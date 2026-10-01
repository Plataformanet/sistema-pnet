<x-mail::message>
Olá {{ $name }},

A proposta **Nº {{ $proposalNumber }}** foi finalizada, mas ainda faltam documentos obrigatórios do vendedor:

@foreach ($missingDocuments as $sellerName => $documents)
**{{ $sellerName }}**: {{ implode(', ', $documents) }}

@endforeach

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
