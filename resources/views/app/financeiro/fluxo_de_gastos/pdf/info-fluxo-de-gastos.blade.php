<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fluxo de Gastos - {{ request('year', now()->year) }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
            color: #111;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 5px;
            text-align: right;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        td.category-name {
            text-align: left;
            font-weight: bold;
            background-color: #fafafa;
        }
        td.subcategory-name {
            text-align: left;
            padding-left: 15px;
            color: #555;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .bg-category {
            background-color: #f9f9f9;
        }
        .font-bold {
            font-weight: bold;
        }
        .summary-row {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        .grand-total-row {
            background-color: #e9e9e9;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Fluxo de Gastos - Ano {{ request('year', now()->year) }}</h2>

    <table>
        <thead>
            <tr>
                <th class="text-left" style="width: 25%;">Categorias</th>
                @foreach($meses as $num => $nome)
                    <th>{{ substr($nome, 0, 3) }}</th>
                @endforeach
                <th>Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($spendingFlow['categories'] as $entry)
                {{-- Linha da Categoria Principal --}}
                <tr class="bg-category font-bold">
                    <td class="category-name">{{ $entry['category']['name'] }}</td>
                    @for($m = 1; $m <= 12; $m++)
                        <td class="text-center" style="color: #888;">
                            @if($entry['has_subcategories'])
                                -
                            @else
                                {{ $entry['months'][$m] > 0 ? number_format($entry['months'][$m] / 100, 2, ',', '.') : '-' }}
                            @endif
                        </td>
                    @endfor
                    <td>
                        {{ number_format($entry['total'] / 100, 2, ',', '.') }}
                    </td>
                </tr>

                {{-- Linhas das Subcategorias se houver --}}
                @if($entry['has_subcategories'])
                    @foreach($entry['subcategories'] as $sub)
                        <tr>
                            <td class="subcategory-name">&nbsp;&nbsp;&nbsp;- {{ $sub['subcategory']['name'] }}</td>
                            @for($m = 1; $m <= 12; $m++)
                                <td class="text-center">
                                    {{ $sub['months'][$m] > 0 ? number_format($sub['months'][$m] / 100, 2, ',', '.') : '-' }}
                                </td>
                            @endfor
                            <td>{{ number_format($sub['total'] / 100, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach

            {{-- Linha de Valores Somados --}}
            <tr class="summary-row">
                <td class="text-left">Valores somados (R$)</td>
                @for($m = 1; $m <= 12; $m++)
                    <td class="text-center">
                        {{ $spendingFlow['totalsByMonth'][$m] > 0 ? number_format($spendingFlow['totalsByMonth'][$m] / 100, 2, ',', '.') : '-' }}
                    </td>
                @endfor
                <td>{{ number_format($spendingFlow['grandTotal'] / 100, 2, ',', '.') }}</td>
            </tr>

            {{-- Resumos do Rodapé --}}
            <tr class="grand-total-row">
                <td class="text-left" colspan="13">Valor Geral</td>
                <td>{{ number_format($spendingFlow['grandTotal'] / 100, 2, ',', '.') }}</td>
            </tr>
            <tr class="grand-total-row">
                <td class="text-left" colspan="13">Média mensal</td>
                <td>{{ number_format($spendingFlow['monthlyAverage'] / 100, 2, ',', '.') }}</td>
            </tr>
            <tr class="grand-total-row">
                <td class="text-left" colspan="13">Valor diário</td>
                <td>{{ number_format($spendingFlow['dailyAverage'] / 100, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
