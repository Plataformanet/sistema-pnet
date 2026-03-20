<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentralUser extends Model
{
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

}
