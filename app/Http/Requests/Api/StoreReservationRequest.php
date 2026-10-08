<?php

namespace App\Http\Requests\Api;

use App\Http\Middleware\ResolveCafe;
use App\Modules\Reservation\Validation\ReservationRules;
use Illuminate\Contracts\Validation\Validator;

/**
 * The full reservation request: the slot being asked for plus who is asking.
 */
class StoreReservationRequest extends QuoteReservationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [...parent::rules(), ...ReservationRules::guest()];
    }

    /**
     * The contact must look like what the guest said it is.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $locale = ResolveCafe::locale($this);
            $value = $this->input('contact_value');

            if ($this->isEmailContact() && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $validator->errors()->add('contact_value', ReservationRules::message('contact_email', $locale));
            }

            if (! $this->isEmailContact() && ! preg_match(ReservationRules::PHONE_PATTERN, $value)) {
                $validator->errors()->add('contact_value', ReservationRules::message('contact_phone', $locale));
            }
        }];
    }

    public function isEmailContact(): bool
    {
        return $this->input('contact_type') === 'email';
    }
}
