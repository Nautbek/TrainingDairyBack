<?php

namespace App\Http\Requests\Donation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDonationRequest extends FormRequest
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
            'uuid' => ['required', 'uuid'],
            'tier' => ['required', 'integer', Rule::in(array_keys(config('donations.tiers')))],
            'payment_method' => ['sometimes', 'string', Rule::in(['sbp'])],
        ];
    }
}
