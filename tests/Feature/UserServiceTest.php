<?php

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = sharedTenant();

    // Cargos e permissões usados nos testes (idempotente: o tenant pode já tê-los semeados).
    $this->tenant->run(function () {
        Permission::findOrCreate('drive.drives.view', 'web');
        Permission::findOrCreate('drive.drives.create', 'web');
        Permission::findOrCreate('finance.accounts.view', 'web');

        Role::findOrCreate('Gestor', 'web');
        Role::findOrCreate('Operador', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    });
});

/**
 * Payload aceito por UserService::store, permitindo sobrescrever campos por teste.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function userPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'password' => 'password',
        'role' => 'Gestor',
        'status' => 1,
        'permissions' => [],
    ], $overrides);
}

/**
 * Nomes das permissões diretas do usuário, relidas do banco.
 *
 * @return array<int, string>
 */
function directPermissionNames(int $userId): array
{
    return test()->tenant->run(function () use ($userId) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return User::findOrFail($userId)->getDirectPermissions()->pluck('name')->sort()->values()->all();
    });
}

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('store cria o usuário com cargo, status e senha com hash', function () {
    $user = app(UserService::class)->store(userPayload(), $this->tenant);

    expect($user->name)->toBe('Jimmy Carter')
        ->and($user->email)->toBe('jimmy@teste.com');

    $this->tenant->run(function () use ($user) {
        $fresh = User::findOrFail($user->id);

        expect($fresh->hasRole('Gestor'))->toBeTrue()
            ->and((int) $fresh->status)->toBe(1)
            ->and(Hash::check('password', $fresh->password))->toBeTrue()
            ->and($fresh->password)->not->toBe('password');
    });
});

test('store sincroniza as permissões diretas informadas', function () {
    $user = app(UserService::class)->store(userPayload([
        'permissions' => ['drive.drives.view', 'finance.accounts.view'],
    ]), $this->tenant);

    expect(directPermissionNames($user->id))->toBe(['drive.drives.view', 'finance.accounts.view']);
});

test('store aceita status desativado', function () {
    $user = app(UserService::class)->store(userPayload(['status' => false]), $this->tenant);

    $this->tenant->run(fn () => expect((int) User::findOrFail($user->id)->status)->toBe(0));
});

// ---------------------------------------------------------------------------
// update — dados básicos
// ---------------------------------------------------------------------------

test('update altera nome e email', function () {
    $user = app(UserService::class)->store(userPayload(), $this->tenant);

    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Atualizado',
        'email' => 'novo@teste.com',
    ], $this->tenant);

    $this->tenant->run(function () use ($user) {
        $fresh = User::findOrFail($user->id);

        expect($fresh->name)->toBe('Jimmy Atualizado')
            ->and($fresh->email)->toBe('novo@teste.com');
    });
});

test('update troca a senha somente quando informada', function () {
    $user = app(UserService::class)->store(userPayload(), $this->tenant);

    // Sem senha: mantém a antiga.
    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'password' => '',
    ], $this->tenant);

    $this->tenant->run(fn () => expect(
        Hash::check('password', User::findOrFail($user->id)->password)
    )->toBeTrue());

    // Com senha: troca.
    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'password' => 'nova-senha',
    ], $this->tenant);

    $this->tenant->run(fn () => expect(
        Hash::check('nova-senha', User::findOrFail($user->id)->password)
    )->toBeTrue());
});

test('update troca o cargo do usuário', function () {
    $user = app(UserService::class)->store(userPayload(), $this->tenant);

    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'role' => 'Operador',
    ], $this->tenant);

    $this->tenant->run(function () use ($user) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $fresh = User::findOrFail($user->id);

        expect($fresh->hasRole('Operador'))->toBeTrue()
            ->and($fresh->hasRole('Gestor'))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// update — permissões (regressão: eram silenciosamente descartadas)
// ---------------------------------------------------------------------------

test('update sincroniza as permissões diretas', function () {
    $user = app(UserService::class)->store(userPayload(), $this->tenant);

    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'permissions' => ['drive.drives.view', 'drive.drives.create'],
    ], $this->tenant);

    expect(directPermissionNames($user->id))->toBe(['drive.drives.create', 'drive.drives.view']);
});

test('update substitui as permissões diretas anteriores', function () {
    $user = app(UserService::class)->store(userPayload([
        'permissions' => ['drive.drives.view', 'drive.drives.create'],
    ]), $this->tenant);

    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'permissions' => ['finance.accounts.view'],
    ], $this->tenant);

    expect(directPermissionNames($user->id))->toBe(['finance.accounts.view']);
});

test('update com permissions vazio remove todas as permissões diretas', function () {
    // Regressão: com empty() no lugar de array_key_exists(), desmarcar todas virava no-op.
    $user = app(UserService::class)->store(userPayload([
        'permissions' => ['drive.drives.view'],
    ]), $this->tenant);

    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'permissions' => [],
    ], $this->tenant);

    expect(directPermissionNames($user->id))->toBe([]);
});

test('update sem a chave permissions preserva as permissões diretas', function () {
    $user = app(UserService::class)->store(userPayload([
        'permissions' => ['drive.drives.view'],
    ]), $this->tenant);

    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
    ], $this->tenant);

    expect(directPermissionNames($user->id))->toBe(['drive.drives.view']);
});

// ---------------------------------------------------------------------------
// update — status (regressão: era ignorado)
// ---------------------------------------------------------------------------

test('update desativa o usuário', function () {
    // Regressão: com empty(), o false de "desativar" era ignorado e o status nunca mudava.
    $user = app(UserService::class)->store(userPayload(['status' => true]), $this->tenant);

    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'status' => false,
    ], $this->tenant);

    $this->tenant->run(fn () => expect((int) User::findOrFail($user->id)->status)->toBe(0));
});

test('update reativa o usuário', function () {
    $user = app(UserService::class)->store(userPayload(['status' => false]), $this->tenant);

    app(UserService::class)->update((string) $user->id, [
        'name' => 'Jimmy Carter',
        'email' => 'jimmy@teste.com',
        'status' => true,
    ], $this->tenant);

    $this->tenant->run(fn () => expect((int) User::findOrFail($user->id)->status)->toBe(1));
});

// ---------------------------------------------------------------------------
// Consultas e remoção
// ---------------------------------------------------------------------------

test('findById retorna o usuário com os cargos carregados', function () {
    $user = app(UserService::class)->store(userPayload(), $this->tenant);

    $found = app(UserService::class)->findById((string) $user->id, $this->tenant);

    expect($found->id)->toBe($user->id)
        ->and($found->relationLoaded('roles'))->toBeTrue()
        ->and($found->roles->pluck('name')->all())->toBe(['Gestor']);
});

test('findAll retorna os usuários com id, nome, email, cargo e status', function () {
    $user = app(UserService::class)->store(userPayload(), $this->tenant);

    $users = app(UserService::class)->findAll($this->tenant);

    $entry = collect($users)->firstWhere('id', $user->id);

    expect($entry)->not->toBeNull()
        ->and($entry['name'])->toBe('Jimmy Carter')
        ->and($entry['email'])->toBe('jimmy@teste.com')
        ->and($entry['role'])->toBe('Gestor')
        ->and(array_keys($entry))->toEqualCanonicalizing(['id', 'name', 'email', 'role', 'status']);
});

test('destroy remove o usuário', function () {
    $user = app(UserService::class)->store(userPayload(), $this->tenant);

    app(UserService::class)->destroy((string) $user->id, $this->tenant);

    $this->tenant->run(fn () => expect(User::find($user->id))->toBeNull());
});
