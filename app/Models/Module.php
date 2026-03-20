<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'icon',
        'is_core',
        'requires_modules',
        'route_prefix',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'requires_modules' => 'array',
    ];

    // 1. Module PERTENCE A MUITOS Plans através de pivot (many to many)
    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_modules')
            ->withPivot('is_included', 'additional_price')
            ->withTimestamps();
    }

    // 2. Module PERTENCE A MUITOS Tenants através de pivot (many to many)
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules')
            ->withPivot('is_active', 'activated_at', 'expires_at')
            ->withTimestamps();
    }

    // 3. Módulos que este módulo requer
    public function dependencies()
    {
        if (empty($this->requires_modules)) {
            return collect([]);
        }

        return static::whereIn('id', $this->requires_modules)->get();
    }

    // 4. Verificar se todas dependências estão satisfeitas para um tenant
    public function canBeActivatedFor(Tenant $tenant): bool
    {
        if (empty($this->requires_modules)) {
            return true;
        }

        $activatedModules = $tenant->activeModules()->pluck('id')->toArray();

        foreach ($this->requires_modules as $requiredId) {
            if (!in_array($requiredId, $activatedModules)) {
                return false;
            }
        }

        return true;
    }
}
