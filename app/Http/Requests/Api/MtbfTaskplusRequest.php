<?php

namespace App\Http\Requests\Api;

class MtbfTaskplusRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer'],
            'area_id' => ['sometimes', 'integer'],
        ];
    }
}
