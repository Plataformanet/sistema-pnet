<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MPWebhooks extends Model
{
    use SoftDeletes;

    protected $table = 'mp_webhooks';

    protected $fillable = [
        'topic',
        'resource_id',
        'payload',
        'processed',
        'processed_at',
        'error',
        'attempts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed' => 'boolean',
            'processed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
