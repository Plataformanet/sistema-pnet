<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'price',
        'max_users',
        'max_storage_gb',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_users' => 'integer',
        'max_storage_gb' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    // 1. Plan TEM MUITOS Tenants (has many)
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    // 2. Plan TEM MUITOS Modules através de pivot (many to many)
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'plan_modules')
            ->withPivot('is_included', 'additional_price')
            ->withTimestamps();
    }

    // 3. Apenas módulos incluídos no plano
    public function includedModules()
    {
        return $this->modules()->wherePivot('is_included', true);
    }

    // 4. Módulos disponíveis como add-on
    public function addonModules()
    {
        return $this->modules()->wherePivot('is_included', false);
    }
}
