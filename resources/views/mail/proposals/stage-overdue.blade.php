<x-mail::message>
Olá {{ $name }},

A etapa **{{ $stageName }}** da proposta **Nº {{ $proposalNumber }}** atingiu o prazo de alerta e ainda não foi concluída.

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
