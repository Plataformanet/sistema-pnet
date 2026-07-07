<?php

namespace App\Jobs;

use App\Enums\RolesEnum;
use App\Enums\TenantProvisioningStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Providers\TenancyServiceProvider;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Última etapa do pipeline de provisionamento do tenant (executa depois de
 * CreateDatabase e MigrateDatabase). Roda dentro do banco já migrado do tenant
 * e cria roles, permissões e o usuário administrador a partir do payload que o
 * TenantService guardou em `tenant->seed`.
 *
 * @see TenantService
 * @see TenancyServiceProvider::events()
 */
class SeedTenantDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function handle(): void
    {
        $seed = $this->tenant->seed ?? [];
        $admin = $seed['admin'] ?? null;

        // Tenants sem payload de seed (ex.: tenant compartilhado dos testes) não
        // precisam de provisionamento inicial.
        if ($admin === null) {
            return;
        }

        /** @var array<int, array{name: string, display_name: string}> $permissions */
        $permissions = $seed['permissions'] ?? [];

        $this->tenant->run(function () use ($admin, $permissions): void {
            DB::transaction(function () use ($admin, $permissions): void {
                $now = now();

                Role::insert(
                    collect(RolesEnum::all())
                        ->map(fn (string $name): array => [
                            'name' => $name,
                            'guard_name' => 'web',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all()
                );

                if ($permissions !== []) {
                    Permission::insert(
                        collect($permissions)
                            ->map(fn (array $permission): array => [
                                'name' => $permission['name'],
                                'display_name' => $permission['display_name'],
                                'guard_name' => 'web',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ])
                            ->all()
                    );
                }

                app(PermissionRegistrar::class)->forgetCachedPermissions();

                $adminRole = Role::where('name', RolesEnum::ADMIN->label())->firstOrFail();
                $adminRole->givePermissionTo(Permission::all());

                $user = User::create([
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    // Já vem com hash do TenantService; o cast `hashed` detecta e
                    // não re-hasheia.
                    'password' => $admin['password'],
                ]);

                $user->assignRole($adminRole);
            });
        });

        // Marca como pronto e descarta as credenciais transitórias do tenant.
        // O seed já foi comitado com sucesso aqui; se só esta escrita falhar,
        // apenas reportamos — deixar o job estourar acionaria failed() e
        // deletaria um tenant já válido.
        try {
            $this->tenant->update([
                'provisioning_status' => TenantProvisioningStatus::READY->value,
                'seed' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        // Registra a causa antes de limpar — senão a falha some com o tenant.
        if ($exception !== null) {
            report($exception);
        }

        $this->tenant->update([
            'provisioning_status' => TenantProvisioningStatus::FAILED->value,
        ]);

        // Remove o tenant meio-provisionado (dispara DeleteDatabase e derruba o
        // banco órfão), preservando o comportamento anterior de não deixar
        // resíduos quando o provisionamento falha.
        $this->tenant->delete();
    }
}
