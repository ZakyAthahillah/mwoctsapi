<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateOperationRequest extends BaseApiFormRequest
{
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
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
