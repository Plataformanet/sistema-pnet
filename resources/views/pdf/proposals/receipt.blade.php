@php
    use App\Enums\ReceiptType;
    use App\Support\Money;
    $document = preg_replace('/\D/', '', $receipt->document);
    $formattedDocument = strlen($document) === 11
        ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $document)
        : preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $document);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo — Proposta Nº {{ $proposal->number }}</title>
    @include('pdf._styles')
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

    <div class="signature"><span>{{ $company['name'] ?? config('app.name') }}</span></div>

    <div class="footer">
        {{ $company['city'] ?? '' }}{{ ! empty($company['city']) ? ', ' : '' }}{{ now()->locale('pt_BR')->translatedFormat('d \d\e F \d\e Y') }}
    </div>
</body>
</html>
