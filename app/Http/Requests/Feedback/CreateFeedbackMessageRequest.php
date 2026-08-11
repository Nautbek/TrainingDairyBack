<?php

namespace App\Http\Requests\Feedback;

class CreateFeedbackMessageRequest extends UuidHeaderRequest
{
    protected function extraRules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'uuid.required' => 'User UUID required',
            'text.required' => 'Text param required',
        ];
    }
}
