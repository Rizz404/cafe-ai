<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SpeakMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guest_token' => ['required', 'uuid'],
            'message_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
