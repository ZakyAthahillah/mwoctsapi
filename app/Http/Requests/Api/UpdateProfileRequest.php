<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'image' => ['nullable'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }
}
