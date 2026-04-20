<?php

namespace App\Http\Requests\Api;

class StoreGroupRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
