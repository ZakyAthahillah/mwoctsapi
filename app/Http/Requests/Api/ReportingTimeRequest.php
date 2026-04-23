<?php

namespace App\Http\Requests\Api;

class ReportingTimeRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reporting_date' => ['sometimes', 'date'],
        ];
    }
}
