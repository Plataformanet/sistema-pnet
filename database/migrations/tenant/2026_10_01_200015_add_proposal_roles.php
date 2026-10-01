<?php

use App\Enums\RolesEnum;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cargos usados pelo módulo de Propostas. A visibilidade por registro (parceiro,
 * vendedor do imóvel e cliente só veem as próprias propostas) fica no
 * `Proposal::scopeVisibleTo`; aqui só entram as permissões padrão, que o
 * administrador pode ajustar na tela de cargos.
 */
return new class extends Migration
{
    /**
     * O Analista tem acesso total ao grupo "Documentações" (cadastros,
     * propostas, calculadora e orçamentos).
     *
     * @return array<string, array<int, string>>
     */
    private function defaults(): array
    {
        return [
            RolesEnum::ANALYST->label() => Permission::where('name', 'like', 'documents.%')->pluck('name')->all(),
            RolesEnum::CLIENT->label() => [
                'documents.proposals.view',
            ],
            RolesEnum::PROPERTY_SELLER->label() => [
                'documents.proposals.view',
            ],
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->defaults() as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            $existing = Permission::whereIn('name', $permissions)->pluck('name')->all();

            $role->givePermissionTo($existing);
        }

        $partner = Role::where('name', RolesEnum::PARTNER->label())->first();
        $partnerPermissions = Permission::whereIn('name', [
            'documents.proposals.view',
            'documents.proposals.create',
            'documents.proposal_documents.view',
        ])->pluck('name')->all();

        $partner?->givePermissionTo($partnerPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::whereIn('name', array_keys($this->defaults()))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
