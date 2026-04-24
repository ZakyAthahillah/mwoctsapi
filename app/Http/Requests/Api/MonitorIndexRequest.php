<?php

namespace App\Http\Requests\Api;

class MonitorIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'period_start' => ['sometimes', 'date'],
            'period_end' => ['sometimes', 'date'],
        ];
    }
}
