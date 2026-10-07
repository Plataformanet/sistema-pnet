<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Leva ao banco do tenant os cargos e as permissões que o plano dele libera.
 *
 * A fonte é o catálogo central (`permissions`, por módulo): o tenant recebe só
 * as permissões dos módulos ativos dele. Como as rotas são protegidas apenas
 * pelo middleware `permission:`, é esse filtro que faz o plano valer no backend.
 */
class TenantPermissionService
{
    /**
     * Achata as permissões dos módulos num payload enxuto.
     *
     * Deduplica por `name`: dois módulos podem declarar a mesma permissão, e o
     * índice único do Spatie (`name` + `guard_name`) não aceita repetição.
     *
     * @param  Collection<int, Module>  $modules
     * @return array<int, array{name: string, display_name: string}>
     */
    public function forModules(Collection $modules): array
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
     * Permissões liberadas pelos módulos ativos do tenant. A consulta herda a
     * conexão central do Tenant, então funciona também dentro do contexto do
     * tenant (ex.: numa migration de tenant).
     *
     * @return array<int, array{name: string, display_name: string}>
     */
    public function forTenant(Tenant $tenant): array
    {
        return $this->forModules($tenant->activeModules()->with('permissions')->get());
    }

    /**
     * Grava cargos e permissões no banco do tenant ATUAL e concede todas as
     * permissões ao Administrador. Só adiciona: não remove permissões que já
     * existem no tenant. Deve rodar dentro do contexto do tenant.
     *
     * @param  array<int, array{name: string, display_name: string}>  $permissions
     */
    public function apply(array $permissions): void
    {
        $now = now();

        // Upsert, e não insert: o tenant pode já ter parte dos cargos e das
        // permissões (sync repetido, ou a migration add_proposal_roles, que cria
        // alguns cargos), e um insert simples violaria o índice único
        // name + guard_name.
        Role::upsert(
            collect(RolesEnum::all())
                ->map(fn (string $name): array => [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all(),
            ['name', 'guard_name'],
            ['updated_at'],
        );

        if ($permissions !== []) {
            Permission::upsert(
                collect($permissions)
                    ->map(fn (array $permission): array => [
                        'name' => $permission['name'],
                        'display_name' => $permission['display_name'],
                        'guard_name' => 'web',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all(),
                ['name', 'guard_name'],
                ['display_name', 'updated_at'],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::where('name', RolesEnum::ADMIN->label())
            ->firstOrFail()
            ->givePermissionTo(Permission::all());
    }

    /**
     * Concede as permissões padrão dos cargos operacionais, entre as que o
     * tenant tem. Roda só no provisionamento: depois disso quem decide é o
     * administrador, na tela de cargos. Por isso fica fora do apply(), que o
     * sync reexecuta. Deve rodar dentro do contexto do tenant, depois do apply().
     */
    public function applyRoleDefaults(): void
    {
        $existing = Permission::pluck('name');

        $defaults = [
            // O Analista tem acesso total ao grupo "Documentações".
            RolesEnum::ANALYST->label() => $existing
                ->filter(fn (string $name) => str_starts_with($name, 'documents.'))
                ->all(),
            RolesEnum::CLIENT->label() => ['documents.proposals.view'],
            RolesEnum::PROPERTY_SELLER->label() => ['documents.proposals.view'],
            RolesEnum::PARTNER->label() => [
                'documents.proposals.view',
                'documents.proposals.create',
                'documents.proposal_documents.view',
            ],
        ];

        foreach ($defaults as $roleName => $permissions) {
            Role::where('name', $roleName)
                ->firstOrFail()
                ->givePermissionTo($existing->intersect($permissions)->values()->all());
        }
    }

    /**
     * Sincroniza um tenant já provisionado com o plano dele: calcula as
     * permissões pelo catálogo central e as aplica no banco do tenant.
     */
    public function sync(Tenant $tenant): void
    {
        $permissions = $this->forTenant($tenant);

        $tenant->run(function () use ($permissions): void {
            DB::transaction(fn () => $this->apply($permissions));
        });
    }
}
