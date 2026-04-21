<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateOperationRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['division_id', 'part_id'] as $field) {
            $value = $this->input($field);

            if ($value !== null && ! is_array($value)) {
                $this->merge([
                    $field => [$value],
                ]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $operationId = $this->route('operation')?->id;

        return [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('operations', 'code')
                    ->ignore($operationId)
                    ->where(function ($query) {
                        $query->where('status', '<>', 99);

                        return $this->input('area_id') === null
                            ? $query->whereNull('area_id')
                            : $query->where('area_id', $this->input('area_id'));
                    }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'division_id' => ['nullable', 'array'],
            'division_id.*' => [
                'integer',
                'distinct',
                Rule::exists('divisions', 'id')->where(function ($query) {
                    $query->where('status', '<>', 99);

                    return $this->input('area_id') === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $this->input('area_id'));
                }),
            ],
            'part_id' => ['nullable', 'array'],
            'part_id.*' => [
                'integer',
                'distinct',
                Rule::exists('parts', 'id')->where(function ($query) {
                    $query->where('status', '<>', 99);

                    return $this->input('area_id') === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $this->input('area_id'));
                }),
            ],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
