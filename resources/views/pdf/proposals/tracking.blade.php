<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acompanhamento da Proposta Nº {{ $proposal->number }}</title>
    @include('pdf._styles')
</head>
<body>
    @include('pdf._header')

    <h2>Acompanhamento da Proposta Nº {{ $proposal->number }}</h2>

    <table>
        <tr><td class="label">Proponentes</td><td>{{ $proposal->applicants->map(fn ($applicant) => $applicant->contact->name_corporatereason)->join(', ') }}</td></tr>
        <tr><td class="label">Status</td><td>{{ $proposal->status->label() }}</td></tr>
        @if ($proposal->finished_at)
            <tr><td class="label">Finalizada em</td><td>{{ $proposal->finished_at->format('d/m/Y') }}</td></tr>
        @endif
    </table>

    <h3>Etapas</h3>
    <table>
        <tr><th>#</th><th>Etapa</th><th>Situação</th><th>Início</th><th>Conclusão</th><th>Data</th><th>Observação</th></tr>
        @foreach ($proposal->stages as $index => $proposalStage)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $proposalStage->stage?->name }}</td>
                <td>
                    @switch($proposalStage->status)
                        @case(\App\Models\ProposalStage::STATUS_COMPLETED) Concluída @break
                        @case(\App\Models\ProposalStage::STATUS_IN_PROGRESS) Em andamento @break
                        @default Pendente
                    @endswitch
                </td>
                <td>{{ $proposalStage->started_at?->format('d/m/Y') }}</td>
                <td>{{ $proposalStage->completed_at?->format('d/m/Y') }}</td>
                <td>{{ $proposalStage->date?->format('d/m/Y') }}</td>
                <td>{{ $proposalStage->notes }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer">Documento gerado em {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
