<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
