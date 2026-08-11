<?php

namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Базовый request для эндпоинтов фидбек-чата: подтягивает X-User-UUID в
 * поле uuid, требует его — треды всегда привязаны к пользователю
 * (анонимных обращений в этом флоу нет, в отличие от старого /user_feedback).
 */
abstract class UuidHeaderRequest extends FormRequest
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
        return array_merge([
            'uuid' => ['required', 'uuid'],
        ], $this->extraRules());
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    abstract protected function extraRules(): array;
}
