<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreMachineRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('machines', 'code')->where(function ($query) {
                    $query->where('status', '<>', 99);

                    return $this->input('area_id') === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $this->input('area_id'));
                }),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['present', 'nullable', 'string'],
            'image' => ['present', 'nullable', 'string', 'max:255'],
            'image_side' => ['present', 'nullable', 'string', 'max:255'],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
