<?php

namespace Modules\TrainingDiary\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExerciseEntryRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'logged_at' => ['required', 'date'],
            'client_id' => ['nullable', 'integer', 'min:0'],
            'measurement_type' => ['nullable', 'string', 'in:reps,time,distance'],
            'approaches' => ['array'],
            'approaches.*.weight' => ['nullable', 'numeric', 'min:0'],
            'approaches.*.repeat_count' => ['nullable', 'integer', 'min:0'],
            'approaches.*.comment' => ['nullable', 'string'],
            'approaches.*.client_id' => ['nullable', 'integer', 'min:0'],
            'approaches.*.duration_seconds' => ['nullable', 'integer', 'min:0'],
            'approaches.*.distance_meters' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
