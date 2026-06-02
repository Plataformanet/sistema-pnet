<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function store(array $data, Tenant $tenant): Product
    {
        return $tenant->run(fn () => Product::create($data));
    }

    public function update(array $data, string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => Product::findOrFail($id)->update($data));
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => Product::findOrFail($id)->delete());
    }

    public function findById(string $id, Tenant $tenant): Product
    {
        return $tenant->run(fn () => Product::findOrFail($id));
    }

    public function findAll(Tenant $tenant, int $perPage = 15): Collection
    {
        return $tenant->run(fn () => Product::all());

        // return $tenant->run(fn() => Product::paginate($perPage));
    }
}
