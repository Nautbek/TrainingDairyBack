<?php

namespace Modules\TrainingDiary\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexExerciseEntryRequest extends FormRequest
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

        // Доказательство владения (см. "Аккаунт по email", DeviceToken::issueFor) —
        // без него uuid сам по себе не секрет, это и была дыра, из-за которой пул сняли.
        if ($this->header('X-Device-Token') && ! $this->has('device_token')) {
            $this->merge(['device_token' => $this->header('X-Device-Token')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'device_token' => ['required', 'string'],
            // Отсутствует при первой синхронизации на новом устройстве — тогда отдаём всю историю.
            'since' => ['nullable', 'date'],
        ];
    }
}
