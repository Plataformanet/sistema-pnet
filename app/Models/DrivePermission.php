<?php

namespace App\Models;

use App\Enums\PermissionTypeDriveEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrivePermission extends Model
{
    protected $fillable = [
        'drive_id',
        'user_id',
        'permission_type',
    ];

    protected $casts = [
        'permission_type' => PermissionTypeDriveEnum::class
    ];

    public function drive(): BelongsTo
    {
        return $this->belongsTo(Drive::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
