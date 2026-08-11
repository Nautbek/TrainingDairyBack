<?php

namespace Modules\Nutrition\Http\Requests\Admin;

use Modules\Nutrition\Enums\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('barcode')) {
            $barcode = preg_replace('/\D+/', '', (string) $this->input('barcode'));
            $this->merge(['barcode' => $barcode !== '' ? $barcode : null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            '_product_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'proteins' => ['required', 'numeric', 'min:0'],
            'fats' => ['required', 'numeric', 'min:0'],
            'carbs' => ['required', 'numeric', 'min:0'],
            'calories' => ['required', 'numeric', 'min:0'],
            'author_uuid' => ['required', 'uuid', 'exists:users,uuid'],
            'status' => ['required', new Enum(ProductStatus::class)],
        ];
    }
}
