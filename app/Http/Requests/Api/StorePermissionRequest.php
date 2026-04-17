<?php

namespace App\Http\Requests\Api;

class StorePermissionRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191', 'unique:permissions,name'],
            'guard_name' => ['sometimes', 'string', 'max:191'],
        ];
    }
}
