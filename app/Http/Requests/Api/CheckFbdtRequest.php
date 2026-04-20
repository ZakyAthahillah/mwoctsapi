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
            'year' => ['required', 'integer', 'digits:4'],
        ];
    }
}
