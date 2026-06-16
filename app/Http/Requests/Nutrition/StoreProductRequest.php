<?php

namespace App\Http\Requests\Nutrition;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'uuid' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'proteins' => ['required', 'numeric', 'min:0'],
            'fats' => ['required', 'numeric', 'min:0'],
            'carbs' => ['required', 'numeric', 'min:0'],
            'calories' => ['required', 'numeric', 'min:0'],
        ];
    }
}
