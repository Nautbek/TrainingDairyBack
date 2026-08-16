<?php

namespace Modules\TrainingDiary\Http\Requests\Admin;

use App\Models\FeedbackThread;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplyFeedbackThreadRequest extends FormRequest
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
            '_thread_id' => ['required', 'integer'],
            'status' => ['required', Rule::in([FeedbackThread::STATUS_OPEN, FeedbackThread::STATUS_CLOSED])],
            'reply' => ['nullable', 'string'],
        ];
    }
}
