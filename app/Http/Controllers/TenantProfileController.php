<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantProfileController extends Controller
{
    public function __construct(protected UserProfileService $userProfileService) {}

    /**
     * Renderiza a página de perfil do usuário.
     */
    public function edit()
    {
        $user = auth()->user();

        return Inertia::render('tenant/profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo_url' => $user->photo_url,
            ],
        ]);
    }

    /**
     * Atualiza as informações pessoais do usuário e o avatar no MinIO.
     */
    public function updateProfile(UpdateUserProfileRequest $request)
    {
        try {
            /** @var User $user */
            $user = $request->user();

            $this->userProfileService->updateProfile(
                $user,
                $request->validated(),
                tenant(),
                $request->file('photo'),
                $request->boolean('remove_photo')
            );

            return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar perfil do usuário: '.$th->getMessage(), [
                'exception' => $th,
            ]);

            return redirect()->back()->with('error', 'Ocorreu um erro ao atualizar seu perfil.');
        }
    }

    /**
     * Atualiza a senha do usuário autenticado.
     */
    public function updatePassword(UpdateUserPasswordRequest $request)
    {
        try {
            /** @var User $user */
            $user = $request->user();

            $this->userProfileService->updatePassword($user, $request->input('password'), tenant());

            return redirect()->back()->with('success', 'Senha alterada com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao alterar senha do usuário: '.$th->getMessage(), [
                'exception' => $th,
            ]);

            return redirect()->back()->with('error', 'Ocorreu um erro ao alterar sua senha.');
        }
    }

    /**
     * Transmite a foto de perfil do usuário a partir do MinIO.
     */
    public function showAvatar(): StreamedResponse|Response
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->userProfileService->getAvatarStream($user, tenant());
    }
}
