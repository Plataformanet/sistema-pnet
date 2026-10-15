<x-mail::message>
Olá {{ $name }},

O arquivo ZIP com os documentos de **{{ $proposalCount }} proposta(s) finalizada(s)** que você solicitou já está pronto.

<x-mail::button :url="$downloadUrl">
Baixar ZIP
</x-mail::button>

O link vale por {{ $linkTtlHours }} horas e só funciona para você, com o seu login no sistema.

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
