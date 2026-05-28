<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quota extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'quotable_type',
        'quotable_id',
        'quota_number',
        'value',
        'description',
        'due_date',
        'payment_date',
        'status',
    ];

    protected $casts = [
        'quota_number' => 'integer',
        'value'        => 'integer',
        'status'       => 'integer',
        'due_date'     => 'date',
        'payment_date' => 'date',
    ];

    public function quotable(): MorphTo
    {
        return $this->morphTo();
    }
}
