<?php

namespace App\Http\Requests\Nutrition;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchProductByBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('barcode')) {
            $barcode = preg_replace('/\D+/', '', (string) $this->input('barcode'));
            $this->merge(['barcode' => $barcode]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:32'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
