<?php

namespace App\Http\Requests\Api;

class TargetIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'part_id' => ['nullable', 'integer', 'exists:parts,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
