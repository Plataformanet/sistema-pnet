<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateRoleRequest extends StoreRoleRequest
{
    public function rules(): array
    {
        $id = $this->route('id');

        return array_merge(parent::rules(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($id),
            ],
        ]);
    }
}
