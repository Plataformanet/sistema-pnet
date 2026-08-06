<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveLog extends Model
{
    protected $fillable = [
        'log',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'log' => 'array',
        ];
    }
}
