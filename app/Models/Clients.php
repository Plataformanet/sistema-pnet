<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    protected $fillable = [];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
