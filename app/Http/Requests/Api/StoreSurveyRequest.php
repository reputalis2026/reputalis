<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_code' => ['required', 'string', 'max:255'],
            'employee_code' => ['nullable', 'string', 'max:255'],
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'improvement_reason_code' => ['nullable', 'string', 'max:255'],
            'improvement_option_id' => ['nullable', 'string', 'uuid'],
            'locale_used' => ['nullable', 'string', 'max:10'],
            'device_hash' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $score = (int) $this->input('score');
            $hasReasonCode = filled($this->input('improvement_reason_code'));
            $hasOptionId = filled($this->input('improvement_option_id'));

            if ($score >= 1 && $score <= 3 && ! $hasReasonCode && ! $hasOptionId) {
                $validator->errors()->add(
                    'improvement_option_id',
                    'Debe indicar un motivo de mejora (opción elegida) cuando la puntuación es 1, 2 o 3.'
                );
            }
        });
    }
}
