<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'category_service_id',
        'name',
        'sku',
        'cost_value',
        'sell_value',
        'fees',
        'duration',
        'description',
        'status',
    ];

    public function categoryService()
    {
        return $this->belongsTo(CategoryService::class);
    }
}
