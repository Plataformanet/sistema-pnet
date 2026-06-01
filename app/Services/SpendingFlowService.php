<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\Tenant;

class SpendingFlowService
{
    /**
     * Calcula o fluxo de gastos com totalizadores incluindo subcategorias
     *
     * @param Tenant $tenant
     * @param int|null $categoryId
     * @param int|null $year
     * @return array
     */
    public function calculateSpendingFlow(Tenant $tenant, $categoryId = null, $year = null): array
    {
        return $tenant->run(function () use ($categoryId, $year) {
            $year = $year ?? now()->year;

            // Carrega apenas categorias principais (sem categoria_pai_id)
            $categories = FinancialCategory::with([
                'accountsPayable.installments'               => function ($query) use ($year) {
                    $query->whereYear('due_date', $year);
                },
                'subcategories.accountsPayable.installments' => function ($query) use ($year) {
                    $query->whereYear('due_date', $year);
                }
            ])->when($categoryId !== null, function ($query) use ($categoryId) {
                $query->where('id', $categoryId);
            })->get();

            $totalsByMonth = array_fill(1, 12, 0);
            $grandTotal    = 0;

            $data = $categories->map(function ($category) use (&$totalsByMonth, &$grandTotal) {

                // Processa subcategorias
                $subcategoriesData = [];
                $categoryTotal     = 0;
                $categoryMonths    = array_fill(1, 12, 0); // Categoria principal sempre zerada

                if ($category->subcategories && $category->subcategories->count() > 0) {
                    // TEM SUBCATEGORIAS - categoria principal não mostra valores
                    foreach ($category->subcategories as $subcategory) {
                        $subcategoryTotal  = 0;
                        $subcategoryMonths = [];

                        for ($month = 1; $month <= 12; $month++) {
                            $monthTotal = 0;

                            foreach ($subcategory->accountsPayable as $account) {
                                $monthTotal += $account->installments
                                    ->filter(fn($installment) => $installment->due_date->month == $month)
                                    ->sum('value');
                            }

                            $subcategoryMonths[$month]  = $monthTotal;
                            $subcategoryTotal          += $monthTotal;

                            // Adiciona aos totais gerais
                            $totalsByMonth[$month]  += $monthTotal;
                            $categoryMonths[$month] += $monthTotal; // Acumula no total da categoria
                        }

                        $categoryTotal += $subcategoryTotal;

                        $subcategoriesData[] = [
                            'subcategory' => $subcategory,
                            'months'      => $subcategoryMonths,
                            'total'       => $subcategoryTotal
                        ];
                    }
                } else {
                    // NÃO TEM SUBCATEGORIAS - categoria mostra seus próprios valores
                    for ($month = 1; $month <= 12; $month++) {
                        $monthTotal = 0;

                        foreach ($category->accountsPayable as $account) {
                            $monthTotal += $account->installments
                                ->filter(fn($installment) => $installment->due_date->month == $month)
                                ->sum('value');
                        }

                        $categoryMonths[$month]  = $monthTotal;
                        $categoryTotal          += $monthTotal;
                        $totalsByMonth[$month]  += $monthTotal;
                    }
                }

                $grandTotal += $categoryTotal;

                return [
                    'category'          => $category,
                    'months'            => $categoryMonths,
                    'total'             => $categoryTotal,
                    'subcategories'     => $subcategoriesData,
                    'has_subcategories' => $category->subcategories && $category->subcategories->count() > 0
                ];
            });

            return [
                'categories'     => $data,
                'totalsByMonth'  => $totalsByMonth,
                'grandTotal'     => $grandTotal,
                'monthlyAverage' => $grandTotal / 12,
                'dailyAverage'   => ($grandTotal / 12) / 30
            ];
        });
    }
}
