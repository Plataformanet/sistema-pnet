<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Remove os campos de senha quando vierem vazios, para que a regra
     * "sometimes" os ignore na edição (senha só é validada se preenchida).
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('password'))) {
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return array_merge(parent::rules(), [
            'email' => ['required', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['sometimes', 'confirmed'],
        ]);
    }
}
