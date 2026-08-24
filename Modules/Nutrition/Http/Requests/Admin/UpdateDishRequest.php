<?php

namespace Modules\Nutrition\Http\Requests\Admin;

use Modules\Nutrition\Enums\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDishRequest extends FormRequest
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
            '_dish_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'water_grams' => ['required', 'numeric', 'min:0'],
            'author_uuid' => ['required', 'uuid', 'exists:users,uuid'],
            'status' => ['required', new Enum(ProductStatus::class)],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.name' => ['required', 'string', 'max:255'],
            'ingredients.*.product_uuid' => ['nullable', 'uuid'],
            'ingredients.*.proteins' => ['required', 'numeric', 'min:0'],
            'ingredients.*.fats' => ['required', 'numeric', 'min:0'],
            'ingredients.*.carbs' => ['required', 'numeric', 'min:0'],
            'ingredients.*.grams' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
