<?php

namespace App\Services;

use App\Enums\TenantProvisioningStatus;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class TenantService
{
    /**
     * Cria o tenant (linha central, domínio e módulos) e dispara o
     * provisionamento assíncrono do banco do tenant.
     *
     * O trabalho pesado — criar o banco, rodar as migrations e semear
     * roles/permissões/usuário admin — acontece no pipeline do evento
     * `TenantCreated` (ver TenancyServiceProvider), fora do request. Por isso o
     * payload de seed é calculado aqui e guardado em `tenant->seed`, para o job
     * SeedTenantDatabase consumir depois das migrations.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Tenant
    {
        $plan = Plan::with('includedModules.permissions')->find($data['plan_id']);

        if (! $plan) {
            throw new \Exception('Plano não encontrado');
        }

        $modules = $this->resolveActivatableModules($plan);

        // Dispara o pipeline de provisionamento (assíncrono em produção). O
        // payload de seed viaja junto do tenant para o job SeedTenantDatabase.
        $tenant = Tenant::create([
            'name' => $data['name'],
            'plan_id' => $plan->id,
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
            'provisioning_status' => TenantProvisioningStatus::PENDING->value,
            'seed' => [
                'admin' => [
                    'name' => $data['userName'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ],
                'permissions' => $this->buildPermissionsPayload($modules),
            ],
        ]);

        try {
            $tenant->domains()->create([
                'domain' => $data['domain'],
            ]);

            foreach ($modules as $module) {
                $tenant->modules()->attach($module->id, [
                    'is_active' => true,
                    'activated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Desfaz o tenant já criado (dispara DeleteDatabase e derruba o banco).
            $tenant->delete();
            throw $e;
        }

        return $tenant;
    }

    /**
     * Resolve os módulos incluídos no plano que serão ativados na criação do
     * tenant, validando as dependências dentro do próprio conjunto incluído
     * (o tenant é novo, então "dependência satisfeita" = o módulo requerido
     * também está incluído no plano).
     *
     * Para ativar um módulo avulso num tenant já existente, use
     * {@see canActivateModule()}.
     *
     * @return Collection<int, Module>
     */
    protected function resolveActivatableModules(Plan $plan): Collection
    {
        $modules = $plan->includedModules;
        $includedIds = $modules->pluck('id')->all();

        foreach ($modules as $module) {
            $missing = array_diff($module->requires_modules ?? [], $includedIds);

            if ($missing !== []) {
                $dependencies = Module::whereIn('id', $missing)->pluck('name')->join(', ');
                throw new \Exception("Requer módulos: {$dependencies}");
            }
        }

        return $modules;
    }

    /**
     * Achata as permissões dos módulos num payload enxuto para o job de seed.
     *
     * Deduplica por `name`: dois módulos incluídos podem declarar a mesma
     * permissão, e o índice único do Spatie (`name` + `guard_name`) faria o
     * `Permission::insert()` do seeder falhar.
     *
     * @param  Collection<int, Module>  $modules
     * @return array<int, array{name: string, display_name: string}>
     */
    protected function buildPermissionsPayload(Collection $modules): array
    {
        return $modules
            ->flatMap(fn (Module $module) => $module->permissions)
            ->map(fn ($permission): array => [
                'name' => $permission->name,
                'display_name' => $permission->display_name,
            ])
            ->unique('name')
            ->values()
            ->all();
    }

    /**
     * Autoriza ativar UM módulo específico num tenant JÁ existente (ex.: ligar um
     * add-on depois do cadastro): valida contra os módulos já ativos no banco.
     *
     * Não confundir com {@see resolveActivatableModules()}, que valida o
     * conjunto de módulos incluídos no plano na criação do tenant (quando ainda
     * não há módulos ativos). São contextos diferentes de propósito.
     */
    public function canActivateModule(Tenant $tenant, Module $module): bool
    {
        // 1. Verificar se módulo está no plano
        $planHasModule = $tenant->plan->modules()
            ->where('modules.id', $module->id)
            ->exists();

        if (! $planHasModule) {
            throw new \Exception('Módulo não disponível no plano atual');
        }

        // 2. Verificar dependências
        if (! $module->canBeActivatedFor($tenant)) {
            $dependencies = $module->dependencies()->pluck('name')->join(', ');
            throw new \Exception("Requer módulos: {$dependencies}");
        }

        // 3. Verificar se não está bloqueado
        if (! $tenant->is_active) {
            throw new \Exception('Tenant bloqueado');
        }

        return true;
    }
}
