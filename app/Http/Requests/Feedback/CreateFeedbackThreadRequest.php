<?php

namespace App\Http\Requests\Feedback;

class CreateFeedbackThreadRequest extends UuidHeaderRequest
{
    protected function extraRules(): array
    {
        return [
            'app' => ['required', 'string', 'max:40'],
            'text' => ['required', 'string', 'min:10', 'max:1000'],
            'device_info' => ['nullable', 'string', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'uuid.required' => 'User UUID required',
            'app.required' => 'App param required',
            'text.required' => 'Text param required',
            'text.min' => 'Text is too short',
        ];
    }
}
