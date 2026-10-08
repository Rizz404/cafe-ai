<?php

namespace App\Http\Requests\Admin;

use App\Modules\Reservation\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The cafe team confirms or cancels a pending reservation; it never goes back
 * to pending.
 */
class UpdateReservationStatusRequest extends FormRequest
{
    /**
     * A record of another cafe is "not found" before anything is validated.
     */
    public function authorize(): bool
    {
        $record = $this->route('reservation');

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
            'status' => ['required', Rule::in([ReservationStatus::Confirmed->value, ReservationStatus::Cancelled->value])],
        ];
    }
}
