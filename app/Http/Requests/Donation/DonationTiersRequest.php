<?php

namespace App\Http\Requests\Donation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * uuid необязателен здесь (в отличие от UserUuidRequest): список тарифов
 * должен открываться и без него, просто без персональной скидки.
 */
class DonationTiersRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['sometimes', 'uuid'],
        ];
    }
}
