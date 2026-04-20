<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreReasonRequest extends BaseApiFormRequest
{
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
            'division_id' => ['present', 'nullable', 'integer', 'exists:divisions,id'],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
