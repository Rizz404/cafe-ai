<?php

namespace App\Modules\Reservation\Support;

use App\Models\Cafe;
use App\Models\Reservation;

/**
 * Builds the WhatsApp / phone / email hand-over links that carry a
 * reservation summary (or a plain "talk to staff" request) to the cafe team.
 */
class ReservationHandover
{
    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    public function forReservation(Cafe $cafe, Reservation $reservation, string $locale): array
    {
        $message = $this->reservationMessage($reservation, $locale);

        return $this->links($cafe, $message, $this->subject('reservation', $locale).' '.$reservation->reference);
    }

    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    public function forStaff(Cafe $cafe, string $locale): array
    {
        return $this->links($cafe, $this->subject('staff_message', $locale), $this->subject('staff', $locale));
    }

    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    private function links(Cafe $cafe, string $message, string $subject): array
    {
        $whatsapp = preg_replace('/\D+/', '', (string) $cafe->whatsapp);
        $phone = preg_replace('/[^\d+]/', '', (string) $cafe->phone);

        return [
            'whatsapp_url' => $whatsapp !== '' ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message) : null,
            'phone_url' => $phone !== '' ? 'tel:'.$phone : null,
            'email_url' => filled($cafe->email) ? 'mailto:'.$cafe->email.'?subject='.rawurlencode($subject).'&body='.rawurlencode($message) : null,
        ];
    }

    private function reservationMessage(Reservation $reservation, string $locale): string
    {
        $reservation->loadMissing('seatingArea');

        $label = fn (string $key): string => trans('handover.message.'.$key, [], $locale);

        return implode("\n", [
            $label('intro'),
            '',
            $label('name').": {$reservation->guest_name}",
            $label('date').': '.$reservation->reservation_date->locale($locale)->translatedFormat('l, j F Y'),
            $label('time').': '.TableAvailability::slotRange($reservation->time_slot),
            $label('guests').": {$reservation->guests}",
            $label('seating').': '.$reservation->seatingArea->translatedName($locale),
            $label('occasion').': '.($reservation->occasion ?: '-'),
            $label('special').': '.($reservation->notes ?: '-'),
            '',
            $label('reference').": {$reservation->reference}",
        ]);
    }

    private function subject(string $key, string $locale): string
    {
        return trans('handover.subject.'.$key, [], $locale);
    }
}
