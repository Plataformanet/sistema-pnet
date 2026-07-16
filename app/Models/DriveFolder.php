<?php

namespace App\Models;

use App\Policies\DrivePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(DrivePolicy::class)]
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

    public function getPath(): string
    {
        if ($this->parent) {
            return $this->parent->getPath().'/'.$this->name;
        }

        return $this->name;
    }

    public function getBreadcrumbAttribute()
    {
        $breadcrumb = collect();
        $current = $this;

        while ($current) {
            $breadcrumb->prepend($current); // adiciona no início
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    public function isDescendantOf(DriveFolder $folder): bool
    {
        $current = $this->parent;
        while ($current) {
            if ($current->id === $folder->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }
}
