@php use App\Support\Money; @endphp
<x-mail::message>
Olá {{ $name }}, estamos enviando o resultado do orçamento feito conosco.

**Orçamento Nº {{ $quoteNumber }}** — válido até {{ $validUntil }}

<x-mail::table>
| Descrição | Valor |
| :-------- | ----: |
@foreach ($breakdown['acts'] as $act)
| {{ $act['descricao'] ?? '' }} | {{ Money::format($act['subtotal'] ?? 0) }} |
@endforeach
@foreach ($breakdown['extra_fees'] as $fee)
| {{ $fee['description'] }} | {{ Money::format($fee['amount']) }} |
@endforeach
| **{{ $breakdown['show_grand_total'] ? 'Total do cálculo' : 'TOTAL' }}** | **{{ Money::format($breakdown['calculation_total']) }}** |
@if ($breakdown['itbi'] !== null)
| ITBI | {{ Money::format($breakdown['itbi']) }} |
@endif
@foreach ($breakdown['services'] as $service)
| {{ $service['name'] }} | {{ Money::format($service['amount']) }} |
@endforeach
@if ($breakdown['show_grand_total'])
| **TOTAL** | **{{ Money::format($breakdown['grand_total']) }}** |
@endif
</x-mail::table>

O orçamento completo segue em anexo, em PDF.

{{ __('fee_calculator.legal_notice', [], 'pt_BR') }}

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
