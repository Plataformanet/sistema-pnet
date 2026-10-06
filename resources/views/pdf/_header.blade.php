<div class="header">
    <table>
        <tr>
            <td style="width: 40%">
                @if ($logo)
                    <img src="{{ $logo }}" alt="Logo">
                @else
                    <strong>{{ ($company['trade_name'] ?? null) ?: (($company['name'] ?? null) ?: config('app.name')) }}</strong>
                @endif
            </td>
            <td class="company">
                <strong>{{ ($company['trade_name'] ?? null) ?: (($company['name'] ?? null) ?: config('app.name')) }}</strong><br>
                @if (! empty($company['cnpj'])) CNPJ: {{ $company['cnpj'] }}<br> @endif
                @if (! empty($company['street']))
                    {{ $company['street'] }}, {{ ($company['number'] ?? null) ?: 's/n' }} — {{ $company['city'] ?? '' }}/{{ $company['state'] ?? '' }}<br>
                @endif
                {{ $company['phone'] ?? '' }} {{ $company['email'] ?? '' }}
            </td>
        </tr>
    </table>
</div>
