<?php

namespace App\Services;

use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class ProductCategoryService
{
    public function store(array $data, Tenant $tenant): ProductCategory
    {
        return $tenant->run(fn () => ProductCategory::create($data));
    }

    public function update(string $id, array $data, Tenant $tenant): bool
    {
        return $tenant->run(fn () => ProductCategory::findOrFail($id)->update($data));
    }

    public function destroy(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => ProductCategory::findOrFail($id)->delete());
    }

    public function findById(string $id, Tenant $tenant): ProductCategory
    {
        return $tenant->run(fn () => ProductCategory::findOrFail($id));
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => ProductCategory::all());
    }

    public function findAllActive(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => ProductCategory::where('status', true)->get());
    }
}
