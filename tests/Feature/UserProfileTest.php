<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

it('permite que usuário logado atualize seu perfil e foto de avatar no MinIO', function () {
    Storage::fake(config('bucket.disk'));

    $this->tenant->run(function () {
        $user = User::factory()->create([
            'name' => 'Nome Antigo',
            'email' => 'antigo@teste.com',
        ]);

        $this->actingAs($user);

        $response = $this->get('http://test.localhost/profile');
        $response->assertOk();

        $photo = UploadedFile::fake()->image('avatar.jpg', 300, 300);

        $postResponse = $this->post('http://test.localhost/profile', [
            'name' => 'Nome Atualizado',
            'email' => 'novo@teste.com',
            'photo' => $photo,
        ]);

        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        $user->refresh();
        expect($user->name)->toBe('Nome Atualizado');
        expect($user->email)->toBe('novo@teste.com');
        expect($user->photo)->not->toBeNull();

        Storage::disk(config('bucket.disk'))->assertExists($user->photo);
    });
});

it('permite que usuário altere sua senha com sucesso', function () {
    $this->tenant->run(function () {
        $user = User::factory()->create([
            'password' => Hash::make('senha-antiga123'),
        ]);

        $this->actingAs($user);

        $response = $this->put('http://test.localhost/profile/password', [
            'current_password' => 'senha-antiga123',
            'password' => 'NovaSenhaSegura#2026',
            'password_confirmation' => 'NovaSenhaSegura#2026',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        expect(Hash::check('NovaSenhaSegura#2026', $user->password))->toBeTrue();
    });
});

it('valida a senha atual ao tentar alterar a senha', function () {
    $this->tenant->run(function () {
        $user = User::factory()->create([
            'password' => Hash::make('senha-correta'),
        ]);

        $this->actingAs($user);

        $response = $this->put('http://test.localhost/profile/password', [
            'current_password' => 'senha-errada',
            'password' => 'NovaSenha#123',
            'password_confirmation' => 'NovaSenha#123',
        ]);

        $response->assertSessionHasErrors('current_password');
    });
});
