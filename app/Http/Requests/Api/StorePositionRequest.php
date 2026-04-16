<?php

namespace App\Http\Requests\Api;

class StorePositionRequest extends BaseApiFormRequest
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
            'description' => ['present', 'nullable', 'string'],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
