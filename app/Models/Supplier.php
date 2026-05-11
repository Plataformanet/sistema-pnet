<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'responsible_person',
        'description',
        'supply_category',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
