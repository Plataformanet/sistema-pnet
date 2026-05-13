<?php

namespace App\Services;

use App\Models\CategoryProduct;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class CategoryProductService
{
    public function store(array $data, Tenant $tenant): CategoryProduct
    {
        return $tenant->run(fn() => CategoryProduct::create($data));
    }

    public function update(string $id, array $data, Tenant $tenant): bool
    {
        return $tenant->run(fn() => CategoryProduct::findOrFail($id)->update($data));
    }

    public function destroy(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn() => CategoryProduct::findOrFail($id)->delete());
    }

    public function findById(string $id, Tenant $tenant): CategoryProduct
    {
        return $tenant->run(fn() => CategoryProduct::findOrFail($id));
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn() => CategoryProduct::all());
    }

    public function findAllActive(Tenant $tenant): Collection
    {
        return $tenant->run(fn() => CategoryProduct::where('status', true)->get());
    }
}
