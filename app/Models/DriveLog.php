<?php

namespace App\Models;

use App\Enums\DriveLogActionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveLog extends Model
{
    protected $fillable = [
        'user_id',
        'drive_id',
        'action',
        'ip_address',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => DriveLogActionEnum::class,
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
