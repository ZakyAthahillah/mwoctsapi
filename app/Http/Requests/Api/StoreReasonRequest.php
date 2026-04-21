<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreReasonRequest extends BaseApiFormRequest
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
        $areaId = $this->authenticatedAreaId();

        return [
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('reasons', 'code')->where(function ($query) use ($areaId) {
                    $query->where('status', '<>', 99);

                    return $areaId === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $areaId);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'division_id' => ['present', 'nullable', 'array'],
            'division_id.*' => [
                'integer',
                'distinct',
                Rule::exists('divisions', 'id')->where(function ($query) use ($areaId) {
                    $query->where('status', '<>', 99);

                    return $areaId === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $areaId);
                }),
            ],
            'part_id' => ['nullable', 'array'],
            'part_id.*' => [
                'integer',
                'distinct',
                Rule::exists('parts', 'id')->where(function ($query) use ($areaId) {
                    $query->where('status', '<>', 99);

                    return $areaId === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $areaId);
                }),
            ],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
