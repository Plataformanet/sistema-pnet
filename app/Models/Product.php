<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $fillable = [
        'category_product_id',
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
        'cost_value'    => 'integer',
        'sell_value'    => 'integer',
        'manage_stock'  => 'boolean',
        'current_stock' => 'integer',
        'min_stock'     => 'integer',
        'status'        => 'boolean',
    ];

    public function categoryProduct()
    {
        return $this->belongsTo(CategoryProduct::class);
    }
}
