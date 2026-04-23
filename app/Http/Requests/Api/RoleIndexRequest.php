<?php

namespace App\Http\Requests\Api;

class RoleIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
