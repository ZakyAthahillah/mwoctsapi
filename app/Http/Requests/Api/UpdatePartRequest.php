<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdatePartRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['operation_id', 'reason_id'] as $field) {
            if ($this->has($field) && ! is_array($this->input($field)) && $this->input($field) !== null) {
                $this->merge([
                    $field => [$this->input($field)],
                ]);
            }
        }
    }

    public function rules(): array
    {
        $partId = $this->route('part')?->id;
        $areaId = $this->input('area_id');

        return [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('parts', 'code')
                    ->ignore($partId)
                    ->where(function ($query) {
                        $query->where('status', '<>', 99);

                        return $this->input('area_id') === null
                            ? $query->whereNull('area_id')
                            : $query->where('area_id', $this->input('area_id'));
                    }),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['present', 'nullable', 'string'],
            'status' => ['required', 'integer', 'between:0,99'],
            'operation_id' => ['nullable', 'array'],
            'operation_id.*' => [
                'integer',
                Rule::exists('operations', 'id')->where(function ($query) use ($areaId) {
                    $query->where('status', '<>', 99);

                    return $areaId === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $areaId);
                }),
            ],
            'reason_id' => ['nullable', 'array'],
            'reason_id.*' => [
                'integer',
                Rule::exists('reasons', 'id')->where(function ($query) use ($areaId) {
                    $query->where('status', '<>', 99);

                    return $areaId === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $areaId);
                }),
            ],
        ];
    }
}
