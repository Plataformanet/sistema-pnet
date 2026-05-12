<?php

namespace App\Services;

use App\Models\CategoryProduct;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class CategoryProductService
{
    public function store(array $data, Tenant $tenant): CategoryProduct
    {
        return $tenant->run(function () use ($data) {
            return CategoryProduct::create($data);
        });
    }

    public function update(string $id, array $data, Tenant $tenant): bool
    {
        return $tenant->run(function () use ($id, $data) {
            return CategoryProduct::findOrFail($id)->update($data);
        });
    }

    public function destroy(string $id, Tenant $tenant): bool
    {
        return $tenant->run(function () use ($id) {
            return CategoryProduct::findOrFail($id)->delete();
        });
    }

    public function findById(string $id, Tenant $tenant): CategoryProduct
    {
        return $tenant->run(function () use ($id) {
            return CategoryProduct::findOrFail($id);
        });
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(function () {
            return CategoryProduct::all();
        });
    }

    public function findAllActive(Tenant $tenant): Collection
    {
        return $tenant->run(function () {
            return CategoryProduct::where('status', true)->get();
        });
    }
}
