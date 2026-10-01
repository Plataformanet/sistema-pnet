<x-mail::message>
Olá {{ $name }},

Foi registrado um novo lançamento na proposta **Nº {{ $proposalNumber }}**:

- **Descrição:** {{ $description }}
- **Valor:** {{ $amount }}
@if ($date)
- **Data:** {{ $date }}
@endif

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
