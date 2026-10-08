<?php

namespace App\Modules\Reservation\Validation;

use App\Models\Cafe;
use App\Models\Reservation;
use Illuminate\Validation\Rule;

/**
 * The one set of reservation rules, shared by the guest-facing wizard's form
 * requests so the quote step and the final submission can never disagree.
 */
final class ReservationRules
{
    public const PHONE_PATTERN = '/^\+?[0-9\s\-().]{6,20}$/';

    public const CONTACT_TYPES = ['whatsapp', 'phone', 'email'];

    /**
     * What the guest asks for: where, when and how many.
     *
     * @return array<string, mixed>
     */
    public static function slot(Cafe $cafe): array
    {
        $today = now($cafe->timezone)->toDateString();

        return [
            'seating_area_slug' => ['required', 'string', 'max:120'],
            'reservation_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$today],
            'time_slot' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'occasion' => ['nullable', Rule::in(Reservation::OCCASIONS)],
            'locale' => ['nullable', Rule::in(config('cafe.locales'))],
        ];
    }

    /**
     * Who is asking, and how to reach them.
     *
     * @return array<string, mixed>
     */
    public static function guest(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:100'],
            'contact_type' => ['required', Rule::in(self::CONTACT_TYPES)],
            'contact_value' => ['required', 'string', 'max:120'],
            'special_request' => ['nullable', 'string', 'max:500'],
            'guest_token' => ['nullable', 'uuid'],
        ];
    }

    /**
     * Guest-facing wording: every generic rule failure reads the same.
     *
     * @return array<string, string>
     */
    public static function messages(string $locale): array
    {
        $messages = trans('reservation.messages', [], $locale);

        return [
            'required' => $messages['invalid'],
            'date_format' => $messages['invalid'],
            'integer' => $messages['invalid'],
            'min' => $messages['invalid'],
            'max' => $messages['invalid'],
            'regex' => $messages['invalid'],
            'in' => $messages['invalid'],
            'uuid' => $messages['invalid'],
            'after_or_equal' => $messages['date_past'],
        ];
    }

    /**
     * The guest-facing message for a domain failure, e.g. "capacity".
     *
     * @param  array<string, string>  $replace
     */
    public static function message(string $key, string $locale, array $replace = []): string
    {
        return trans('reservation.messages.'.$key, $replace, $locale);
    }
}
