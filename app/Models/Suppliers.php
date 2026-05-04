<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suppliers extends Model
{
    protected $fillable = [
        'contact_id',
        'responsible_person',
        'description',
        'supply_category',
    ];

    public function contact()
    {
        return $this->belongsTo(Contacts::class);
    }
}
