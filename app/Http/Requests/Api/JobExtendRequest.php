<?php

namespace App\Http\Requests\Api;

class JobExtendRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'processing_date_finish' => ['required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
