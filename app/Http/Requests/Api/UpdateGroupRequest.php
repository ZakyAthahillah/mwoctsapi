<?php

namespace App\Http\Requests\Api;

class UpdateGroupRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
