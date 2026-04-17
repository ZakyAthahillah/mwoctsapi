<?php

namespace App\Http\Requests\Api;

class CheckFbdtRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'year' => ['required', 'integer', 'digits:4'],
        ];
    }
}
