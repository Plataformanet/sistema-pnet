<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'drive_folder_id',
        'name',
        'document_path',
        'document_size',
        'document_type',
        'modified_by',
        'modified_at',
    ];

    protected $casts = [
        'modified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driveFolder()
    {
        return $this->belongsTo(DriveFolder::class);
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
