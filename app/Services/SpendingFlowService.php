<?php

namespace App\Services;

use App\Models\AccountPayable;
use App\Models\FinancialCategory;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class SpendingFlowService
{
    /**
     * Calcula o fluxo de gastos com totalizadores incluindo subcategorias
     */
    public function calculateSpendingFlow(Tenant $tenant, ?int $categoryId = null, ?int $year = null): array
    {
        return $tenant->run(function () use ($categoryId, $year) {
            $year ??= now()->year;

            $dueDateRange = ["{$year}-01-01", "{$year}-12-31"];

            $categories = FinancialCategory::with([
                'accountsPayable.installments' => function ($query) use ($dueDateRange) {
                    $query->whereBetween('due_date', $dueDateRange);
                },
                'subcategories.accountsPayable.installments' => function ($query) use ($dueDateRange) {
                    $query->whereBetween('due_date', $dueDateRange);
                },
            ])->when($categoryId !== null, function ($query) use ($categoryId) {
                $query->where('id', $categoryId);
            })->get();

            $totalsByMonth = array_fill(1, 12, 0);
            $grandTotal = 0;

            $data = $categories->map(function ($category) use (&$totalsByMonth, &$grandTotal) {

                // Processa subcategorias
                $subcategoriesData = [];
                $categoryTotal = 0;
                $categoryMonths = array_fill(1, 12, 0); // Categoria principal sempre zerada
                $hasSubcategories = $category->subcategories->isNotEmpty();

                if ($hasSubcategories) {
                    // TEM SUBCATEGORIAS - categoria principal não mostra valores próprios no seu próprio nível
                    foreach ($category->subcategories as $subcategory) {
                        $subcategoryMonths = array_fill(1, 12, 0);
                        $subcategoryTotal = 0;

                        foreach ($this->sumInstallmentsByMonth($subcategory->accountsPayable) as $month => $monthTotal) {
                            $subcategoryMonths[$month] = $monthTotal;
                            $subcategoryTotal += $monthTotal;

                            // Acumula nos totais gerais e no total da categoria
                            $totalsByMonth[$month] += $monthTotal;
                            $categoryMonths[$month] += $monthTotal;
                        }

                        $categoryTotal += $subcategoryTotal;

                        $subcategoriesData[] = [
                            'subcategory' => $subcategory->only(['id', 'name']),
                            'months' => $subcategoryMonths,
                            'total' => $subcategoryTotal,
                        ];
                    }

                    // Lançamentos diretos na categoria principal (sem subcategoria)
                    $directAccountsPayable = $category->accountsPayable->filter(function ($ap) {
                        return is_null($ap->financial_subcategory_id);
                    });

                    if ($directAccountsPayable->isNotEmpty()) {
                        $directMonths = array_fill(1, 12, 0);
                        $directTotal = 0;

                        foreach ($this->sumInstallmentsByMonth($directAccountsPayable) as $month => $monthTotal) {
                            $directMonths[$month] = $monthTotal;
                            $directTotal += $monthTotal;
                            $totalsByMonth[$month] += $monthTotal;
                            $categoryMonths[$month] += $monthTotal;
                        }

                        $categoryTotal += $directTotal;

                        $subcategoriesData[] = [
                            'subcategory' => [
                                'id' => null,
                                'name' => 'Sem subcategoria',
                            ],
                            'months' => $directMonths,
                            'total' => $directTotal,
                        ];
                    }
                } else {
                    // NÃO TEM SUBCATEGORIAS - categoria mostra seus próprios valores
                    foreach ($this->sumInstallmentsByMonth($category->accountsPayable) as $month => $monthTotal) {
                        $categoryMonths[$month] = $monthTotal;
                        $categoryTotal += $monthTotal;
                        $totalsByMonth[$month] += $monthTotal;
                    }
                }

                $grandTotal += $categoryTotal;

                return [
                    'category' => $category->only(['id', 'name']),
                    'months' => $categoryMonths,
                    'total' => $categoryTotal,
                    'subcategories' => $subcategoriesData,
                    'has_subcategories' => $hasSubcategories,
                ];
            });

            return [
                'categories' => $data,
                'totalsByMonth' => $totalsByMonth,
                'grandTotal' => $grandTotal,
                'monthlyAverage' => $grandTotal / 12,
                'dailyAverage' => ($grandTotal / 12) / 30,
            ];
        });
    }

    /**
     * Soma o valor das parcelas agrupadas por mês de vencimento, numa única passagem.
     *
     * @param  Collection<int, AccountPayable>  $accountsPayable
     * @return Collection<int, int> Mapa mês (1-12) => soma das parcelas
     */
    private function sumInstallmentsByMonth($accountsPayable): Collection
    {
        return $accountsPayable
            ->flatMap->installments
            ->groupBy(fn ($installment) => $installment->due_date->month)
            ->map->sum('value');
    }
}
