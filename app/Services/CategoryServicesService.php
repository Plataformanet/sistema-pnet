<?php

namespace App\Services;

use App\Models\CategoryService;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class CategoryServicesService
{
    public function store(array $data, Tenant $tenant): CategoryService
    {
        return $tenant->run(fn() => CategoryService::create($data));
    }

    public function update(string $id, array $data, Tenant $tenant): bool
    {
        return $tenant->run(fn() => CategoryService::findOrFail($id)->update($data));
    }

    public function destroy(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn() => CategoryService::findOrFail($id)->delete());
    }

    public function findById(string $id, Tenant $tenant): CategoryService
    {
        return $tenant->run(fn() => CategoryService::findOrFail($id));
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn() => CategoryService::all());
    }

    public function findAllActive(Tenant $tenant): Collection
    {
        return $tenant->run(fn() => CategoryService::where('status', true)->get());
    }
}
