<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'installmentable_type',
        'installmentable_id',
        'installment_number',
        'value',
        'description',
        'due_date',
        'payment_date',
        'status',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'value' => 'integer',
        'status' => 'integer',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

    public function installmentable(): MorphTo
    {
        return $this->morphTo();
    }
}
