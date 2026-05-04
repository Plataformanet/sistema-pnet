<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'type',
        'name_corporatereason',
        'fantasy_name',
        'cpf_cnpj',
        'email',
        'phone',
        'cell_phone',
    ];

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function employee()
    {
        return $this->hasOne(Employees::class);
    }

    public function supplier()
    {
        return $this->hasOne(Suppliers::class);
    }

    public function client()
    {
        return $this->hasOne(Clients::class);
    }
}
