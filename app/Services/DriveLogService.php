<?php

namespace App\Services;

use App\Models\DriveLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\LazyCollection;


class DriveLogService
{
    public function store(array $data)
    {
        $log = [
            'name'          => $data['name'],
            'document_path' => isset($data['document_path']) ? $data['document_path'] : null,
            'document_type' => $data['document_type'] === null ? 1 : $data['document_type'],
            'deleted_by'    => Auth::user()->name,
            'deleted_at'    => Carbon::now()->toDateTimeString(),
        ];

        return DriveLog::create([
            'log' => json_encode($log),
        ]);
    }

    public function findAll(): LazyCollection
    {
        return DriveLog::cursor();
    }
}
