<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReplyToHandoverRequest extends FormRequest
{
    /**
     * A record of another cafe is "not found" before anything is validated.
     */
    public function authorize(): bool
    {
        $record = $this->route('handover');

        if ($record) {
            Gate::authorize('update', $record);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
