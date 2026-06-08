<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_category_id',
        'name',
        'sku',
        'cost_value',
        'sell_value',
        'fees',
        'duration',
        'description',
        'status',
    ];

    protected $casts = [
        'cost_value' => 'integer',
        'sell_value' => 'integer',
        'fees' => 'integer',
        'duration' => 'integer',
        'status' => 'boolean',
    ];

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }
}
