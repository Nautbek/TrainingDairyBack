<?php

namespace Modules\Nutrition\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchDishRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
