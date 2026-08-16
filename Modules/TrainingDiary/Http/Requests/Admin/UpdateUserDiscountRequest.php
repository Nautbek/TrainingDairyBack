<?php

namespace Modules\TrainingDiary\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '_user_id' => ['required', 'integer'],
            'discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
