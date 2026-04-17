<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission');

        return [
            'name' => ['required', 'string', 'max:191', Rule::unique('permissions', 'name')->ignore($permissionId)],
            'guard_name' => ['sometimes', 'string', 'max:191'],
        ];
    }
}
