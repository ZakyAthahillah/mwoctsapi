<?php

namespace App\Http\Requests\Api;

class UpdateSerialNumberRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_serial_number_id' => ['required', 'integer', 'exists:part_serial_numbers,id'],
        ];
    }
}
