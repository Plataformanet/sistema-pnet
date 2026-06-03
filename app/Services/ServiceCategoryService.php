<?php

namespace App\Services;

use App\Models\ServiceCategory;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class ServiceCategoryService
{
    public function store(array $data, Tenant $tenant): ServiceCategory
    {
        return $tenant->run(fn () => ServiceCategory::create($data));
    }

    public function update(string $id, array $data, Tenant $tenant): bool
    {
        return $tenant->run(fn () => ServiceCategory::findOrFail($id)->update($data));
    }

    public function destroy(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => ServiceCategory::findOrFail($id)->delete());
    }

    public function findById(string $id, Tenant $tenant): ServiceCategory
    {
        return $tenant->run(fn () => ServiceCategory::findOrFail($id));
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => ServiceCategory::all());
    }

    public function findAllActive(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => ServiceCategory::where('status', true)->get());
    }
}
