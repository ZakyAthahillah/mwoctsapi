<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'image' => ['present', 'nullable', 'string', 'max:255'],
            'status' => ['required', 'integer', 'between:0,99'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_operator' => ['required', 'boolean'],
            'is_admin' => ['required', 'boolean'],
        ];
    }
}
