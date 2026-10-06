@php
    use App\Enums\ReceiptType;
    use App\Support\Money;
    $document = preg_replace('/\D/', '', $receipt->document);
    $formattedDocument = strlen($document) === 11
        ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $document)
        : preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $document);
    $signedAt = ($receipt->date ?? now())->locale('pt_BR');
    $place = collect([$company['city'] ?? null, $company['state'] ?? null])->filter()->join(' - ');
    $placeAndDate = ($place !== '' ? "{$place}, " : '').$signedAt->format('d').' de '.ucfirst($signedAt->translatedFormat('F')).' de '.$signedAt->format('Y');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo — Proposta Nº {{ $proposal->number }}</title>
    @include('pdf._styles')
    <style>
        body { font-size: 12px; }
        .company, .footer { font-size: 10px; }
        h2 { font-size: 18px; }
        h3 { font-size: 14px; }
        th, td { padding: 6px; }
        @page { margin: 1.2cm 1.2cm 2.6cm; }
        .notes { border: 1px solid #444; height: 90px; margin-top: 20px; padding-top: 14px; text-align: center; font-weight: bold; }
        .place-date { margin: 28px 0 18px; text-align: center; font-weight: bold; }
        .signature-lines { width: 70%; margin-left: 30%; }
        .signature-lines td { border: none; padding: 14px 0 0; }
        .signature-label { width: 1%; white-space: nowrap; padding-right: 4px !important; text-align: right; }
        .signature-line { border-bottom: 1px solid #333 !important; }
        .company-footer { position: fixed; left: 0; right: 0; bottom: -1.9cm; padding: 12px; background: #263233; color: #fff; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    @include('pdf._header')

    <h2>Recibo de {{ $receipt->type->label() }} — Proposta Nº {{ $proposal->number }}</h2>

    <table>
        <tr><td class="label">Nome</td><td>{{ $receipt->name }}</td></tr>
        <tr><td class="label">CPF/CNPJ</td><td>{{ $formattedDocument }}</td></tr>
        @if ($receipt->registration_number)
            <tr><td class="label">Matrícula</td><td>{{ $receipt->registration_number }}</td></tr>
        @endif
        @if ($receipt->notary_name)
            <tr><td class="label">Cartório</td><td>{{ $receipt->notary_name }}</td></tr>
        @endif
        <tr><td class="label">Data</td><td>{{ $receipt->date?->format('d/m/Y') }}</td></tr>
    </table>

    @if ($receipt->type === ReceiptType::GENERAL)
        <h3>Cobranças da proposta</h3>
        <table>
            <tr><th>Tipo</th><th>Descrição</th><th class="money">Valor</th></tr>
            @foreach ($proposal->costItems as $costItem)
                <tr>
                    <td>{{ $costItem->type->label() }}</td>
                    <td>{{ $costItem->description ?? $costItem->extra_fee_description ?? $costItem->costType?->name }}</td>
                    <td class="money">{{ Money::format($costItem->amount) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td colspan="2">Total de emolumentos</td><td class="money">{{ Money::format($feesTotal) }}</td></tr>
            <tr class="total"><td colspan="2">Total gasto</td><td class="money">{{ Money::format($receipt->total_spent) }}</td></tr>
            <tr class="total"><td colspan="2">Valor depositado</td><td class="money">{{ Money::format($receipt->amount_deposited) }}</td></tr>
            <tr class="total"><td colspan="2">Devolução</td><td class="money">{{ Money::format($receipt->refund) }}</td></tr>
        </table>

        <h3>Proponentes</h3>
        <p>{{ $proposal->applicants->map(fn ($applicant) => $applicant->contact->name_corporatereason)->join(', ') }}</p>
    @else
        <h3>Cobrança</h3>
        <table>
            <tr><th>Descrição</th><th class="money">Valor</th></tr>
            <tr>
                <td>{{ $receipt->costItem?->description ?? $receipt->costItem?->costType?->name }}</td>
                <td class="money">{{ Money::format($receipt->total_spent ?? $receipt->costItem?->amount) }}</td>
            </tr>
        </table>
    @endif

    <div class="notes">OBSERVAÇÕES:</div>

    <p class="place-date">{{ $placeAndDate }}</p>

    <table class="signature-lines">
        <tr><td class="signature-label">ASSINATURA:</td><td class="signature-line"></td></tr>
        <tr><td class="signature-label">NOME LEGÍVEL:</td><td class="signature-line"></td></tr>
    </table>

    <div class="company-footer">
        {{ ($company['trade_name'] ?? null) ?: (($company['name'] ?? null) ?: config('app.name')) }}<br>
        {{ collect([$company['email'] ?? null, $company['phone'] ?? null])->filter()->join(' - ') }}
    </div>
</body>
</html>
