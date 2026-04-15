<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class UpdateAreaRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $areaId = $this->route('area')?->id;

        return [
            'code' => ['required', 'string', 'alpha_num', 'max:10', Rule::unique('areas', 'code')->ignore($areaId)],
            'name' => ['required', 'string', 'max:100'],
            'object_name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'integer', 'between:0,99'],
        ];
    }
}
