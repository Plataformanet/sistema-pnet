@php use App\Support\Money; @endphp
<table>
    <tr>
        @foreach ($breakdown['columns'] as $column)
            <th class="{{ $column['numeric'] ? 'money' : '' }}">{{ $column['label'] }}</th>
        @endforeach
    </tr>
    @forelse ($breakdown['acts'] as $act)
        <tr>
            @foreach ($breakdown['columns'] as $column)
                <td class="{{ $column['numeric'] ? 'money' : '' }}">
                    {{ $column['numeric'] ? Money::format($act[$column['key']] ?? 0) : ($act[$column['key']] ?? '') }}
                </td>
            @endforeach
        </tr>
    @empty
        <tr><td colspan="{{ max(count($breakdown['columns']), 1) }}" style="text-align: center">Nenhum registro encontrado</td></tr>
    @endforelse
    @if (count($breakdown['acts']))
        <tr class="total">
            @foreach ($breakdown['columns'] as $column)
                <td class="{{ $column['numeric'] ? 'money' : '' }}">
                    {{ $column['numeric'] ? Money::format($breakdown['subtotals'][$column['key']] ?? 0) : ($loop->first ? 'SUBTOTAIS' : '') }}
                </td>
            @endforeach
        </tr>
    @endif
</table>

<table>
    @foreach ($breakdown['extra_fees'] as $fee)
        <tr><td>{{ $fee['description'] }}</td><td class="money">{{ Money::format($fee['amount']) }}</td></tr>
    @endforeach
    <tr class="total">
        <td>{{ $breakdown['show_grand_total'] ? 'Total do cálculo' : 'TOTAL' }}</td>
        <td class="money">{{ Money::format($breakdown['calculation_total']) }}</td>
    </tr>
    @if ($breakdown['itbi'] !== null)
        <tr><td>ITBI</td><td class="money">{{ Money::format($breakdown['itbi']) }}</td></tr>
    @endif
    @foreach ($breakdown['services'] as $service)
        <tr><td>{{ $service['name'] }}</td><td class="money">{{ Money::format($service['amount']) }}</td></tr>
    @endforeach
    @if ($breakdown['show_grand_total'])
        <tr class="total"><td>TOTAL</td><td class="money">{{ Money::format($breakdown['grand_total']) }}</td></tr>
    @endif
</table>
