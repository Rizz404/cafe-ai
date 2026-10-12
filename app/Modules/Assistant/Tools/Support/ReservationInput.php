<?php

namespace App\Modules\Assistant\Tools\Support;

use App\Models\Cafe;
use App\Modules\Reservation\Support\TableAvailability;
use App\Modules\Reservation\Validation\ReservationRules;
use Carbon\CarbonImmutable;

/**
 * Checks the date and time slot the model passes to the reservation tools, and
 * words the problem so the model can ask the guest for something that works.
 */
class ReservationInput
{
    public function __construct(private readonly TableAvailability $availability) {}

    public function parseDate(Cafe $cafe, string $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value, $cafe->timezone)?->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * A reason the date cannot be booked, or null when it is fine.
     */
    public function dateProblem(Cafe $cafe, CarbonImmutable $date): ?string
    {
        $today = CarbonImmutable::now($cafe->timezone)->startOfDay();

        if ($date->lessThan($today)) {
            return 'That date is in the past. Ask the guest for a date from today onwards.';
        }

        if ($today->diffInDays($date) > TableAvailability::MAX_ADVANCE_DAYS) {
            return 'Tables can only be requested up to '.TableAvailability::MAX_ADVANCE_DAYS.' days ahead. Offer an earlier date, or call request_human_handover for something further out.';
        }

        return null;
    }

    /**
     * A reason the guest's details cannot be used, or null when they are fine.
     * The model decides what to pass, so the same limits as the wizard apply.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function guestProblem(array $arguments): ?string
    {
        $name = trim((string) ($arguments['guest_name'] ?? ''));
        $phone = trim((string) ($arguments['guest_phone'] ?? ''));
        $email = trim((string) ($arguments['guest_email'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100) {
            return 'guest_name is missing or too long (100 characters at most). Ask the guest for their name.';
        }

        if ($phone === '' && $email === '') {
            return 'No way to reach the guest. Ask for a phone or WhatsApp number, or an email address, so the cafe can confirm.';
        }

        if ($phone !== '' && ! preg_match(ReservationRules::PHONE_PATTERN, $phone)) {
            return 'That phone number does not look valid. Ask the guest to check it.';
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'That email address does not look valid. Ask the guest to check it.';
        }

        if (mb_strlen((string) ($arguments['notes'] ?? '')) > 500) {
            return 'notes is too long (500 characters at most). Shorten it, or call request_human_handover for a detailed request.';
        }

        return null;
    }

    public function isKnownSlot(Cafe $cafe, string $slot): bool
    {
        return in_array($slot, $this->availability->slotsFor($cafe), true);
    }

    public function unknownSlotMessage(Cafe $cafe): string
    {
        return 'time_slot must be one of the reservation slots: '.implode(', ', $this->availability->slotsFor($cafe)).'.';
    }
}
