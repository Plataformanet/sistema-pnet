<?php

namespace App\Models;

use App\Enums\TenantProvisioningStatus;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->id)) {
                $tenant->id = (string) Str::uuid();
            }
        });
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'domain',
            'plan_id',
            'is_active',
            'trial_ends_at',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Status atual do provisionamento do banco do tenant.
     */
    public function provisioningStatus(): TenantProvisioningStatus
    {
        return TenantProvisioningStatus::tryFrom((string) $this->provisioning_status)
            ?? TenantProvisioningStatus::PENDING;
    }

    /**
     * Indica se o banco do tenant já foi criado, migrado e semeado.
     */
    public function isProvisioned(): bool
    {
        return $this->provisioningStatus() === TenantProvisioningStatus::READY;
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
            ->withPivot('is_active', 'activated_at', 'expires_at')
            ->withTimestamps();
    }

    /**
     * Módulos ativos
     */
    public function activeModules()
    {
        return $this->modules()
            ->wherePivot('is_active', true)
            ->where(function ($query) {
                $query->whereNull('tenant_modules.expires_at')
                    ->orWhere('tenant_modules.expires_at', '>', now());
            });
    }

    /**
     * Verifica se o tenant tem um módulo ativo
     */
    public function hasModule(array $moduleSlugs): array
    {
        foreach ($moduleSlugs as $slug) {
            $hasModule[$slug] = $this->activeModules()
                ->where('slug', $slug)
                ->exists();
        }

        return $hasModule;
    }

    public function modulesByTenants()
    {
        $slugs = $this->modules()->pluck('slug');

        foreach ($slugs as $slug) {
            $getSlugs[] = $slug;
        }

        return $getSlugs;
    }

    /**
     * Verifica se está em trial
     */
    // public function isOnTrial(): bool
    // {
    //     return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    // }

    /**
     * Verifica se o trial expirou
     */
    // public function trialExpired(): bool
    // {
    //     return $this->trial_ends_at && $this->trial_ends_at->isPast();
    // }
}
