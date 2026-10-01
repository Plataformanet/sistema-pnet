@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('pdf._styles')
</head>
<body>
    @include('pdf._header')

    <h2>{{ $title }}</h2>

    <table>
        <tr>
            <th>Nº</th>
            <th>Cadastro</th>
            <th>Proponentes</th>
            <th>Criador</th>
            <th>Banco</th>
            <th>Imóvel</th>
            <th class="money">Valor de compra</th>
            <th>Status</th>
            <th>Etapa atual</th>
        </tr>
        @forelse ($proposals as $proposal)
            <tr>
                <td>{{ $proposal->number }}</td>
                <td>{{ $proposal->created_at?->format('d/m/Y') }}</td>
                <td>{{ $proposal->applicants->map(fn ($applicant) => $applicant->contact->name_corporatereason)->join(', ') }}</td>
                <td>{{ $proposal->creator?->name }}</td>
                <td>{{ $proposal->bank?->name }}</td>
                <td>{{ $proposal->property_condition->label() }}</td>
                <td class="money">{{ Money::format($proposal->purchase_value) }}</td>
                <td>{{ $proposal->status->label() }}</td>
                <td>{{ $proposal->currentStage?->started_at ? $proposal->currentStage->stage?->name : 'Não iniciada' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align: center">Nenhuma proposta encontrada.</td></tr>
        @endforelse
    </table>

    <div class="footer">Total: {{ $proposals->count() }} proposta(s) — gerado em {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
