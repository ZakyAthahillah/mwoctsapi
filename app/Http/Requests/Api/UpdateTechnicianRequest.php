<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateTechnicianRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $technicianId = $this->route('technician')?->id;

        return [
            'area_id' => ['present', 'nullable', 'integer', 'exists:areas,id'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('technicians', 'code')
                    ->ignore($technicianId)
                    ->where(function ($query) {
                        $query->where('status', '<>', 99);

                        return $this->input('area_id') === null
                            ? $query->whereNull('area_id')
                            : $query->where('area_id', $this->input('area_id'));
                    }),
            ],
            'name' => ['required', 'string', 'max:100'],
            'division_id' => ['present', 'nullable', 'integer', 'exists:divisions,id'],
            'status' => ['required', 'integer', 'between:0,99'],
            'group_id' => ['present', 'nullable', 'integer'],
        ];
    }
}
