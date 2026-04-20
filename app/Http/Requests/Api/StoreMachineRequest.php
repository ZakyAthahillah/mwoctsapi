<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreMachineRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $positionIds = $this->input('position_id');
        $payload = [];

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
            $payload['position_id'] = array_values($positionIds);
        }

        if (! $this->exists('image')) {
            $payload['image'] = null;
        }

        if (! $this->exists('image_side')) {
            $payload['image_side'] = null;
        }

        if (! $this->exists('status')) {
            $payload['status'] = 1;
        }

        if ($payload !== []) {
            $this->merge($payload);
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
                'max:30',
                Rule::unique('machines', 'code')->where(function ($query) use ($areaId) {
                    $query->where('status', '<>', 99);

                    return $areaId === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $areaId);
                }),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['present', 'nullable', 'string'],
            'image' => ['present', 'nullable', function (string $attribute, mixed $value, \Closure $fail) {
                $this->validateImageInput($attribute, $value, $fail);
            }],
            'image_side' => ['present', 'nullable', function (string $attribute, mixed $value, \Closure $fail) {
                $this->validateImageInput($attribute, $value, $fail);
            }],
            'position_id' => ['sometimes', 'array'],
            'position_id.*' => [
                'integer',
                'distinct',
                Rule::exists('positions', 'id')->where(function ($query) use ($areaId) {
                    $query->where('status', '<>', 99);

                    return $areaId === null
                        ? $query->whereNull('area_id')
                        : $query->where('area_id', $areaId);
                }),
            ],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }

    private function validateImageInput(string $attribute, mixed $value, \Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_string($value)) {
            if (mb_strlen($value) > 255) {
                $fail("The {$attribute} field must not be greater than 255 characters.");
            }

            return;
        }

        if (is_object($value) && method_exists($value, 'isValid')) {
            if (! $value->isValid()) {
                $fail("The {$attribute} upload is invalid.");
            }

            $mimeType = (string) $value->getMimeType();
            if (! str_starts_with($mimeType, 'image/')) {
                $fail("The {$attribute} field must be an image.");
            }

            $maxBytes = 5 * 1024 * 1024;
            if ((int) $value->getSize() > $maxBytes) {
                $fail("The {$attribute} field must not be greater than 5120 kilobytes.");
            }

            return;
        }

        $fail("The {$attribute} field format is invalid.");
    }
}
