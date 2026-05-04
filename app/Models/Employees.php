<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    protected $fillable = [
        'contact_id',
        'rg',
        'birth_date',
        'position',
        'salary',
        'hire_date',
    ];

    public function contact()
    {
        return $this->belongsTo(Contacts::class);
    }
}
