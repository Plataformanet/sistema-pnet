<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

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
        return $this->belongsTo(Contact::class);
    }
}
