<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Намеренно без exists:users,email — иначе по ответу валидации можно перечислять,
        // какие email тут зарегистрированы. Ответ контроллера одинаковый в любом случае.
        return [
            'email' => ['required', 'email'],
        ];
    }
}
