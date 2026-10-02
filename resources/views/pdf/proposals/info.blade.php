@php use App\Support\DocumentMask; use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Informativo da Proposta Nº {{ $proposal->number }}</title>
    @include('pdf._styles')
</head>
<body>
    @include('pdf._header')

    <h2>Informativo da Proposta Nº {{ $proposal->number }}</h2>

    <h3>Dados da proposta</h3>
    <table>
        <tr><td class="label">Status</td><td>{{ $proposal->status->label() }}</td></tr>
        <tr><td class="label">Data de cadastro</td><td>{{ $proposal->created_at?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Banco</td><td>{{ $proposal->bank?->name }}</td></tr>
        <tr><td class="label">Tipo de contrato</td><td>{{ $proposal->contractType?->name }}</td></tr>
        @if ($proposal->amortization_table)
            <tr><td class="label">Tabela</td><td>{{ $proposal->amortization_table->label() }}</td></tr>
        @endif
        @if ($proposal->payment_term)
            <tr><td class="label">Prazo</td><td>{{ $proposal->payment_term }} meses</td></tr>
        @endif
        <tr><td class="label">Imóvel</td><td>{{ $proposal->property_condition->label() }}</td></tr>
        <tr><td class="label">Valor de compra</td><td>{{ Money::format($proposal->purchase_value) }}</td></tr>
        <tr><td class="label">Valor de entrada</td><td>{{ Money::format($proposal->down_payment_value) }}</td></tr>
        @if ($proposal->financing_value !== null)
            <tr><td class="label">Valor do financiamento</td><td>{{ Money::format($proposal->financing_value) }}</td></tr>
        @endif
        @if ($proposal->uses_fgts)
            <tr><td class="label">FGTS</td><td>{{ Money::format($proposal->fgts_value) }}</td></tr>
        @endif
        @if ($proposal->subsidy_value !== null)
            <tr><td class="label">Subsídio</td><td>{{ Money::format($proposal->subsidy_value) }}</td></tr>
        @endif
        <tr><td class="label">Criador</td><td>{{ $proposal->creator?->name }}</td></tr>
        <tr><td class="label">Analista</td><td>{{ $proposal->analyst?->name ?? 'Não definido' }}</td></tr>
    </table>

    <h3>Proponentes</h3>
    <table>
        <tr><th>Nome</th><th>CPF</th><th>E-mail</th><th>Telefone</th></tr>
        @foreach ($proposal->applicants as $applicant)
            <tr>
                <td>{{ $applicant->contact->name_corporatereason }}</td>
                <td>{{ $showApplicantContacts ? $applicant->contact->cpf_cnpj : DocumentMask::mask($applicant->contact->cpf_cnpj) }}</td>
                <td>{{ $showApplicantContacts ? $applicant->contact->email : 'Oculto' }}</td>
                <td>{{ $showApplicantContacts ? ($applicant->contact->cell_phone ?: $applicant->contact->phone) : 'Oculto' }}</td>
            </tr>
        @endforeach
    </table>

    @if ($proposal->sellers->isNotEmpty())
        <h3>Vendedores</h3>
        <table>
            <tr><th>Nome / Razão social</th><th>CPF/CNPJ</th><th>E-mail</th></tr>
            @foreach ($proposal->sellers as $seller)
                <tr>
                    <td>{{ $seller->contact->name_corporatereason }}</td>
                    <td>{{ $showSellerContacts ? $seller->contact->cpf_cnpj : DocumentMask::mask($seller->contact->cpf_cnpj) }}</td>
                    <td>{{ $showSellerContacts ? $seller->contact->email : 'Oculto' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($proposal->property)
        <h3>Imóvel</h3>
        <table>
            <tr><td class="label">Tipo</td><td>{{ $proposal->property->propertyType?->name }}</td></tr>
            @if ($proposal->property->development)
                <tr><td class="label">Empreendimento</td><td>{{ $proposal->property->development->name }}</td></tr>
            @endif
            <tr><td class="label">Endereço</td><td>{{ $proposal->property->address }} {{ $proposal->property->number }} {{ $proposal->property->complement }}</td></tr>
            @if ($proposal->property->block || $proposal->property->unit)
                <tr><td class="label">Bloco / Unidade</td><td>{{ $proposal->property->block }} / {{ $proposal->property->unit }}</td></tr>
            @endif
        </table>
    @endif

    @if ($proposal->general_notes)
        <h3>Observações</h3>
        <p>{{ $proposal->general_notes }}</p>
    @endif

    <div class="footer">Documento gerado em {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
