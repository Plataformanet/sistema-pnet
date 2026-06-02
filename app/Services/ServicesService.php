<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class ServicesService
{
    public function store(array $data, Tenant $tenant): Service
    {
        return $tenant->run(fn () => Service::create($data));
    }

    public function update(string $id, array $data, Tenant $tenant): bool
    {
        return $tenant->run(fn () => Service::findOrFail($id)->update($data));
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => Service::all());
    }

    public function findAllActive(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => Service::where('status', true)->get());
    }

    public function findById(string $id, Tenant $tenant): Service
    {
        return $tenant->run(fn () => Service::findOrFail($id));
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => Service::findOrFail($id)->delete());
    }
}
