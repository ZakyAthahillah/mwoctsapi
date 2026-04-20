<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateMachineRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $positionIds = $this->input('position_id');

        if ($positionIds === null && $this->exists('position_ids')) {
            $positionIds = $this->input('position_ids');
        }

        if ($positionIds === null && $this->exists('positions')) {
            $positionIds = $this->input('positions');
        }

        if ($positionIds !== null && ! is_array($positionIds)) {
            $positionIds = [$positionIds];
        }

        if ($positionIds !== null) {
            $this->merge([
                'position_id' => array_values($positionIds),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $machineId = $this->route('machine')?->id;

        return [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('machines', 'code')
                    ->ignore($machineId)
                    ->where(function ($query) {
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
            'position_id' => ['sometimes', 'array'],
            'position_id.*' => [
                'integer',
                'distinct',
                Rule::exists('positions', 'id')->where(function ($query) {
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
