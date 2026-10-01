@php
    $cpf = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $quote->cpf);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento Nº {{ $quote->number }}</title>
    @include('pdf._styles')
</head>
<body>
    @include('pdf._header')

    <h2>Informativo do Orçamento</h2>
    <p style="text-align: center"><strong>Orçamento Nº {{ $quote->number }}</strong> — válido até {{ $quote->feeEstimate?->valid_until?->format('d/m/Y') }}</p>

    @if ($viewer)
        <h3>Administrador ou Analista</h3>
        <p>{{ $viewer->name }}</p>
    @endif

    <h3>Proponente</h3>
    <table>
        <tr><td class="label">Nome</td><td>{{ $quote->name }}</td></tr>
        <tr><td class="label">CPF</td><td>{{ $cpf }}</td></tr>
        <tr><td class="label">Telefone</td><td>{{ $quote->phone }}</td></tr>
        <tr><td class="label">E-mail</td><td>{{ $quote->email }}</td></tr>
        <tr><td class="label">Profissão</td><td>{{ $quote->profession }}</td></tr>
    </table>

    <h3>{{ $quote->feeEstimate?->type->label() }} ({{ $quote->feeEstimate?->state->value }} - {{ $quote->feeEstimate?->municipality_name }})</h3>
    @include('pdf._fee_breakdown')

    <p><strong>{{ __('fee_calculator.legal_notice', [], 'pt_BR') }}</strong></p>

    <div class="footer">
        {{ $company['city'] ?? '' }}{{ ! empty($company['city']) ? ', ' : '' }}{{ now()->locale('pt_BR')->translatedFormat('d \d\e F \d\e Y') }}
    </div>
</body>
</html>
