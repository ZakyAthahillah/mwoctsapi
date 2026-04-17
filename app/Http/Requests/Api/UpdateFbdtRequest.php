<?php

namespace App\Http\Requests\Api;

class UpdateFbdtRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.month' => ['required', 'integer', 'between:1,12', 'distinct'],
            'targets.*.fb' => ['nullable', 'numeric'],
            'targets.*.dt' => ['nullable', 'numeric'],
            'targets.*.mtbf' => ['nullable', 'numeric'],
            'targets.*.mttr' => ['nullable', 'numeric'],
        ];
    }
}
