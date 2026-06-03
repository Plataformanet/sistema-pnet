<?php

namespace App\Services;

use App\Models\FinancialSubcategory;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class FinancialSubcategoryService
{
    public function create(array $data, Tenant $tenant): FinancialSubcategory
    {
        return $tenant->run(function () use ($data) {
            // Verifica se já existe com ou sem soft delete
            $subcategory = FinancialSubcategory::withTrashed()
                ->where('name', $data['name'])
                ->where('financial_category_id', $data['financial_category_id'])
                ->first();

            if ($subcategory) {
                if ($subcategory->trashed()) {
                    $subcategory->restore();
                    $subcategory->update($data);
                } else {
                    throw ValidationException::withMessages([
                        'name' => 'Já existe uma subcategoria com este nome para esta categoria.',
                    ]);
                }

                return $subcategory;
            }

            return FinancialSubcategory::create($data);
        });
    }

    public function update(string $id, array $data, Tenant $tenant): bool
    {
        return $tenant->run(fn () => FinancialSubcategory::findOrFail($id)->update($data));
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => FinancialSubcategory::findOrFail($id)->delete());
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => FinancialSubcategory::all());
    }

    public function findById(string $id, Tenant $tenant): FinancialSubcategory
    {
        return $tenant->run(fn () => FinancialSubcategory::findOrFail($id));
    }

    public function findByCategoriaId(string $id, Tenant $tenant): Collection
    {
        return $tenant->run(function () use ($id) {
            return Cache::remember("subcategorias_cat_{$id}", 60, function () use ($id) {
                return FinancialSubcategory::where('financial_category_id', $id)
                    ->get(['id', 'name']);
            });
        });
    }
}
