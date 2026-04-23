<?php

namespace App\Http\Requests\Api;

class StoreRoleRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'display_name' => ['required', 'string', 'max:191'],
            'guard_name' => ['sometimes', 'string', 'max:191'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
        ];
    }
}
