<?php

namespace App\Http\Requests\TripSplit;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SettleTripRequest extends FormRequest
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
            'trip' => ['required', 'array'],
            'trip.id' => ['required', 'integer'],
            'trip.name' => ['required', 'string', 'max:255'],
            'trip.participants' => ['required', 'array', 'min:2'],
            'trip.participants.*.id' => ['required', 'integer'],
            'trip.participants.*.name' => ['required', 'string', 'max:255'],
            'trip.currencies' => ['nullable', 'array'],
            'trip.currencies.*.code' => ['required', 'string', 'max:10'],
            'trip.currencies.*.rate_to_rub' => ['required', 'numeric', 'gt:0'],
            'trip.transactions' => ['nullable', 'array'],
            'trip.transactions.*.id' => ['required', 'integer'],
            'trip.transactions.*.amount' => ['required', 'numeric', 'gte:0'],
            'trip.transactions.*.currency_code' => ['required', 'string', 'max:10'],
            'trip.transactions.*.description' => ['nullable', 'string', 'max:500'],
            'trip.transactions.*.date' => ['nullable', 'integer'],
            'trip.transactions.*.payer_id' => ['nullable', 'integer'],
            'trip.transactions.*.shares' => ['nullable', 'array'],
            'trip.transactions.*.shares.*.participant_id' => ['required', 'integer'],
            'trip.transactions.*.shares.*.amount' => ['required', 'numeric', 'gte:0'],
            'trip.transactions.*.payer_payments' => ['nullable', 'array'],
            'trip.transactions.*.payer_payments.*.participant_id' => ['required', 'integer'],
            'trip.transactions.*.payer_payments.*.amount' => ['required', 'numeric', 'gte:0'],
            'trip.transactions.*.payer_payments.*.currency_code' => ['required', 'string', 'max:10'],
        ];
    }
}
