<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMachineRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $parts = $this->input('parts');

        if (is_string($parts)) {
            $decodedParts = json_decode($parts, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedParts)) {
                $this->merge([
                    'parts' => $decodedParts,
                ]);
            }
        }

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
            'parts' => ['nullable', 'array'],
            'parts.id' => ['nullable', 'array'],
            'parts.id.*' => ['integer', 'distinct', 'exists:parts,id'],
            'parts.x' => ['nullable', 'array'],
            'parts.x.*' => ['numeric'],
            'parts.y' => ['nullable', 'array'],
            'parts.y.*' => ['numeric'],
            'parts.x_side' => ['nullable', 'array'],
            'parts.x_side.*' => ['numeric'],
            'parts.y_side' => ['nullable', 'array'],
            'parts.y_side.*' => ['numeric'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('parts.id')) {
                return;
            }

            $partIds = $this->input('parts.id', []);
            if (! is_array($partIds)) {
                return;
            }

            foreach (['x', 'y', 'x_side', 'y_side'] as $coordinateKey) {
                $coordinates = $this->input('parts.'.$coordinateKey, []);

                if (is_array($coordinates) && count($coordinates) !== count($partIds)) {
                    $validator->errors()->add(
                        'parts.'.$coordinateKey,
                        'The parts '.$coordinateKey.' field must contain the same number of items as parts id.'
                    );
                }
            }
        });
    }
}
