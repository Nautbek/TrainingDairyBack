<?php

namespace App\Http\Requests\Feedback;

class ListFeedbackThreadsRequest extends UuidHeaderRequest
{
    protected function extraRules(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'uuid.required' => 'User UUID required',
        ];
    }
}
