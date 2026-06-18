<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriveFolder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
    ];

    public function parent()
    {
        return $this->belongsTo(DriveFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(DriveFolder::class, 'parent_id');
    }

    public function drives()
    {
        return $this->hasMany(Drive::class);
    }
}
