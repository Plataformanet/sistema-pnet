<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserProfileService
{
    /**
     * Atualiza dados pessoais e armazena/remove avatar no MinIO dentro de uma transação no escopo do tenant.
     */
    public function updateProfile(User $user, array $data, Tenant $tenant, ?UploadedFile $photoFile = null, bool $removePhoto = false): User
    {
        return $tenant->run(function () use ($user, $data, $photoFile, $removePhoto) {
            return DB::transaction(function () use ($user, $data, $photoFile, $removePhoto) {
                $disk = Storage::disk(config('bucket.disk'));

                // 1. Remover foto se solicitado
                if ($removePhoto && $user->photo) {
                    if ($disk->exists($user->photo)) {
                        $disk->delete($user->photo);
                    }
                    $user->photo = null;
                }

                // 2. Upload de nova foto
                if ($photoFile) {
                    if ($user->photo && $disk->exists($user->photo)) {
                        $disk->delete($user->photo);
                    }

                    $newPhotoPath = $photoFile->store('avatars', config('bucket.disk'));
                    $user->photo = $newPhotoPath;
                }

                $user->name = $data['name'];
                $user->email = $data['email'];
                $user->save();

                return $user;
            });
        });
    }

    /**
     * Atualiza a senha do usuário com hash seguro no escopo do tenant.
     */
    public function updatePassword(User $user, string $newPassword, Tenant $tenant): void
    {
        $tenant->run(function () use ($user, $newPassword) {
            DB::transaction(function () use ($user, $newPassword) {
                $user->password = Hash::make($newPassword);
                $user->save();
            });
        });
    }

    /**
     * Retorna a resposta de stream do avatar do usuário a partir do MinIO.
     */
    public function getAvatarStream(User $user, Tenant $tenant): StreamedResponse|Response
    {
        return $tenant->run(function () use ($user) {
            if (! $user->photo || ! Storage::disk(config('bucket.disk'))->exists($user->photo)) {
                abort(404);
            }

            return Storage::disk(config('bucket.disk'))->response($user->photo);
        });
    }
}
