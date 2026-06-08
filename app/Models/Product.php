<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'name',
        'sku',
        'barcode',
        'cost_value',
        'sell_value',
        'manage_stock',
        'current_stock',
        'min_stock',
        'unit_of_measure',
        'description',
        'status',
    ];

    protected $casts = [
        'cost_value' => 'integer',
        'sell_value' => 'integer',
        'manage_stock' => 'boolean',
        'current_stock' => 'integer',
        'min_stock' => 'integer',
        'status' => 'boolean',
    ];

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }
}
