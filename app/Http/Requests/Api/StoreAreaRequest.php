<?php

namespace App\Http\Requests\Api;

class StoreAreaRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'alpha_num', 'max:10', 'unique:areas,code'],
            'name' => ['required', 'string', 'max:100'],
            'object_name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
