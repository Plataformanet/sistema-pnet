<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes, HasFactory;

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
        return $this->hasOne(Employee::class);
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function proponent()
    {
        return $this->hasOne(Proponents::class);
    }

    public function financialContacts()
    {
        return $this->hasMany(FinancialContact::class);
    }
}
