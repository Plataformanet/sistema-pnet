<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveLog extends Model
{
    protected $fillable = [
        'log',
    ];

    protected $casts = [
        'log' => 'array',
    ];
}
