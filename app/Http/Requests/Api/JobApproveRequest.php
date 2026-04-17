<?php

namespace App\Http\Requests\Api;

class JobApproveRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approved_at' => ['required', 'date'],
            'approved_by' => ['required', 'integer'],
            'approved_notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
