<?php

namespace App\Http\Requests\Api;

class StoreTargetRequest extends BaseApiFormRequest
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
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.month' => ['required', 'integer', 'between:1,12', 'distinct'],
            'targets.*.mtbf' => ['nullable', 'numeric'],
            'targets.*.mttr' => ['nullable', 'numeric'],
        ];
    }
}
