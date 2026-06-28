<?php

namespace App\Http\Requests\MyCar;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmMyCarPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->header('X-User-UUID') && ! $this->has('uuid')) {
            $this->merge(['uuid' => $this->header('X-User-UUID')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'tier' => ['required', 'integer', Rule::in(array_keys(config('mycar.tiers')))],
            'payment_token' => ['required', 'string'],
            'payment_method_type' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
