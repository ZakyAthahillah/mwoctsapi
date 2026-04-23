<?php

namespace App\Http\Requests\Api;

class CheckTargetRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'part_id' => ['required', 'integer', 'exists:parts,id'],
        ];
    }
}
