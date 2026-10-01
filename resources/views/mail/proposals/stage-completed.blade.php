<x-mail::message>
Olá {{ $name }},

A etapa **{{ $stageName }}** da proposta **Nº {{ $proposalNumber }}** foi concluída.

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
