<?php

namespace App\Http\Requests\Api;

class JobStartRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_id' => ['required', 'integer'],
            'processing_date_start' => ['required', 'date'],
        ];
    }
}
