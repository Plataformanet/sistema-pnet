<?php

namespace App\Services;

use App\Models\DriveLog;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\LazyCollection;

class DriveLogService
{
    public function store(array $data): DriveLog
    {
        $log = [
            'name' => $data['name'] ?? null,
            'document_path' => $data['document_path'] ?? null,
            'document_type' => $data['document_type'] ?? null,
            'deleted_by' => Auth::user()?->name,
            'deleted_at' => Carbon::now()->toDateTimeString(),
        ];

        // O cast 'log' => 'array' no model já serializa o JSON; passar o array direto evita dupla codificação
        return DriveLog::create([
            'log' => $log,
        ]);
    }

    public function findAll(Tenant $tenant): LazyCollection
    {
        return $tenant->run(function () {
            return DriveLog::cursor();
        });
    }
}
