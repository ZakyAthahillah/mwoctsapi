<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseApiFormRequest extends FormRequest
{
    protected function authenticatedAreaId(): ?int
    {
        $areaId = $this->user()?->area_id;

        return $areaId !== null ? (int) $areaId : null;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Bad request',
            'data' => null,
            'meta' => null,
            'errors' => $validator->errors()->toArray(),
        ], 400));
    }
}
