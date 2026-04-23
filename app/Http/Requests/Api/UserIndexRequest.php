<?php

namespace App\Http\Requests\Api;

class UserIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'status' => ['nullable', 'integer', 'between:0,99'],
            'is_operator' => ['nullable', 'boolean'],
            'is_admin' => ['nullable', 'boolean'],
        ];
    }
}
