<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FinancialCategoryService
{
    public function create(array $data, Tenant $tenant): FinancialCategory
    {
        return $tenant->run(function () use ($data) {
            // Verifica se já existe com ou sem soft delete
            $category = FinancialCategory::withTrashed()->where('name', $data['name'])->where('type', $data['type'])->first();

            if ($category) {
                if ($category->trashed()) {
                    $category->restore();
                    $category->update($data);
                } else {
                    throw ValidationException::withMessages([
                        'nome' => 'Já existe uma categoria com este nome e tipo.',
                    ]);
                }

                return $category;
            }

            return FinancialCategory::create($data);
        });
    }

    public function update(string $id, array $data, Tenant $tenant): bool
    {
        return $tenant->run(fn () => FinancialCategory::findOrFail($id)->update($data));
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => FinancialCategory::findOrFail($id)->delete());
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => FinancialCategory::all());
    }

    public function findCategoriaContasAPagar(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => FinancialCategory::select('id', 'name', 'active')->where('type', 1)->get());
    }

    public function findCategoriaContasAReceber(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => FinancialCategory::select('id', 'name', 'active')->where('type', 2)->get());
    }

    public function findById(string $id, Tenant $tenant): FinancialCategory
    {
        return $tenant->run(fn () => FinancialCategory::findOrFail($id));
    }
}
