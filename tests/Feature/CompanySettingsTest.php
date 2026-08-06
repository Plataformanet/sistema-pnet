<?php

use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

it('permite que usuário com permissão acesse e atualize as configurações da empresa com upload no MinIO', function () {
    Storage::fake(config('bucket.disk'));

    $this->tenant->run(function () {
        $user = User::factory()->create();

        Permission::firstOrCreate(['name' => 'settings.company.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'settings.company.edit', 'guard_name' => 'web']);
        $user->givePermissionTo(['settings.company.view', 'settings.company.edit']);

        $this->actingAs($user);

        // Access GET page
        $response = $this->get('http://test.localhost/settings/company');
        $response->assertOk();

        // Submit form with logo upload
        $logo = UploadedFile::fake()->image('logo.png', 400, 200);

        $postResponse = $this->post('http://test.localhost/settings/company', [
            'name' => 'Empresa Teste LTDA',
            'trade_name' => 'Empresa Teste',
            'cnpj' => '12.345.678/0001-90',
            'email' => 'contato@empresateste.com.br',
            'phone' => '(11) 99999-9999',
            'zip_code' => '01001-000',
            'street' => 'Praça da Sé',
            'number' => '100',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
            'logo' => $logo,
        ]);

        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        // Check stored settings
        expect(TenantSetting::where('key', 'company.name')->value('value'))->toBe('Empresa Teste LTDA');
        expect(TenantSetting::where('key', 'company.cnpj')->value('value'))->toBe('12.345.678/0001-90');

        $logoPath = TenantSetting::where('key', 'company.logo_path')->value('value');
        expect($logoPath)->not->toBeNull();

        // Check file exists in faked disk
        Storage::disk(config('bucket.disk'))->assertExists($logoPath);
    });
});

it('permite remover o logotipo da empresa', function () {
    Storage::fake(config('bucket.disk'));

    $this->tenant->run(function () {
        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'settings.company.edit', 'guard_name' => 'web']);
        $user->givePermissionTo('settings.company.edit');
        $this->actingAs($user);

        // Pre-store logo setting
        $fakePath = 'company/logo_fake.png';
        Storage::disk(config('bucket.disk'))->put($fakePath, 'fake-content');

        TenantSetting::create([
            'key' => 'company.logo_path',
            'value' => $fakePath,
            'module' => 'company',
            'type' => 'string',
        ]);

        $response = $this->post('http://test.localhost/settings/company', [
            'name' => 'Empresa Teste LTDA',
            'remove_logo' => true,
        ]);

        $response->assertRedirect();
        expect(TenantSetting::where('key', 'company.logo_path')->exists())->toBeFalse();
        Storage::disk(config('bucket.disk'))->assertMissing($fakePath);
    });
});
