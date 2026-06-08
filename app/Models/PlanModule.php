<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanModule extends Pivot
{
    use SoftDeletes;

    protected $table = 'plan_modules';

    public $incrementing = true;

    protected $fillable = [
        'plan_id',
        'module_id',
        'is_included',
        'additional_price',
    ];

    protected $casts = [
        'is_included' => 'boolean',
        'additional_price' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
