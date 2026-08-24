<?php

namespace Modules\Nutrition\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDishRequest extends FormRequest
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
     * Only the raw ingredients + water are accepted from the client — proteins/fats/carbs/
     * calories/total_grams for the dish itself are never accepted here, the server always
     * recomputes them (see Modules\Nutrition\Support\DishNutritionCalculator).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'water_grams' => ['sometimes', 'numeric', 'min:0'],
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
