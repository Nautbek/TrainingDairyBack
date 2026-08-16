<?php

namespace Modules\TrainingDiary\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\TrainingDiary\Enums\FeedbackStatus;

class UpdateFeedbackRequest extends FormRequest
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
            '_feedback_id' => ['required', 'integer'],
            'status' => ['required', new Enum(FeedbackStatus::class)],
            'admin_answer' => ['nullable', 'string'],
        ];
    }
}
