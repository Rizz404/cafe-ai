<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Models\Reservation;
use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Support\ReservationInput;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;
use App\Modules\Reservation\Actions\CreateReservationRequest;
use App\Modules\Reservation\Support\ReservationHandover;
use App\Modules\Reservation\Support\TableAvailability;
use Carbon\CarbonImmutable;

/**
 * Only ever creates a pending request for the cafe to confirm; it never
 * confirms a table and takes no payment.
 */
final class CreateReservationRequestTool implements Tool
{
    public function __construct(
        private readonly TableAvailability $availability,
        private readonly ReservationInput $input,
        private readonly CreateReservationRequest $create,
        private readonly ReservationHandover $handover,
    ) {}

    public function name(): string
    {
        return 'create_reservation_request';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Create a table reservation request once the guest has said yes to the full summary (seating area, date, time slot, party size, name, and a phone/WhatsApp number or email). Call it once per booking. This re-checks availability before booking. It does not charge payment — it creates a pending reservation for the cafe to confirm, and the guest sees the reference card on screen.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'seating_area_slug' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'time_slot' => ['type' => 'string', 'description' => 'Slot start time, HH:MM'],
                        'guests' => ['type' => 'integer', 'minimum' => 1],
                        'occasion' => ['type' => 'string', 'enum' => Reservation::OCCASIONS],
                        'guest_name' => ['type' => 'string'],
                        'guest_phone' => ['type' => 'string', 'description' => 'Phone or WhatsApp number. Required unless guest_email is given.'],
                        'guest_email' => ['type' => 'string', 'description' => 'Required unless guest_phone is given.'],
                        'notes' => ['type' => 'string', 'description' => 'Special request, at most 500 characters'],
                    ],
                    'required' => ['seating_area_slug', 'date', 'time_slot', 'guests', 'guest_name'],
                ],
            ],
        ];
    }

    public function execute(ToolContext $context, array $arguments): ToolResult
    {
        $cafe = $context->cafe;
        $area = CatalogSummaries::seatingAreaBySlug($cafe, (string) ($arguments['seating_area_slug'] ?? ''));

        if (! $area) {
            return new ToolResult('Seating area not found.');
        }

        $date = $this->input->parseDate($cafe, (string) ($arguments['date'] ?? ''));
        $slot = (string) ($arguments['time_slot'] ?? '');

        if (! $date) {
            return new ToolResult('date must be a valid YYYY-MM-DD date.');
        }

        if ($error = $this->input->dateProblem($cafe, $date)) {
            return new ToolResult($error);
        }

        if (! $this->input->isKnownSlot($cafe, $slot)) {
            return new ToolResult($this->input->unknownSlotMessage($cafe));
        }

        if ($problem = $this->input->guestProblem($arguments)) {
            return new ToolResult($problem);
        }

        $guests = (int) ($arguments['guests'] ?? 0);

        if (! $this->availability->fitsParty($area, $guests)) {
            return new ToolResult("This party size does not suit that seating area (it seats {$area->min_guests}-{$area->max_guests} guests). Suggest another seating area, or call request_human_handover for a large group.");
        }

        if ($this->availability->isSlotInPast($cafe, $date, $slot)) {
            return new ToolResult('That time slot has already started. Ask the guest for a later slot.');
        }

        $guestName = trim((string) $arguments['guest_name']);

        if ($existing = $this->alreadyRequested($context, $area->id, $date, $slot, $guests, $guestName)) {
            return $this->confirmation($context, $existing, alreadyRequested: true);
        }

        $occasion = in_array($arguments['occasion'] ?? null, Reservation::OCCASIONS, true) ? $arguments['occasion'] : null;
        $phone = trim((string) ($arguments['guest_phone'] ?? '')) ?: null;
        $email = trim((string) ($arguments['guest_email'] ?? '')) ?: null;

        $reservation = $this->create->handle($cafe, $area, [
            'reservation_date' => $date,
            'time_slot' => $slot,
            'guests' => $guests,
            'occasion' => $occasion,
            'guest_name' => $guestName,
            'guest_email' => $email,
            'guest_phone' => $phone,
            'contact_type' => $phone ? 'phone' : 'email',
            'notes' => trim((string) ($arguments['notes'] ?? '')) ?: null,
        ], $context->conversation);

        if (! $reservation) {
            return new ToolResult('No table is free in that slot any more — availability may have just changed. Call check_table_availability again or offer another slot.');
        }

        return $this->confirmation($context, $reservation);
    }

    /**
     * The model may repeat the call when the guest says yes twice; the same
     * request in the same conversation is answered, not booked again.
     */
    private function alreadyRequested(ToolContext $context, int $areaId, CarbonImmutable $date, string $slot, int $guests, string $guestName): ?Reservation
    {
        return Reservation::query()
            ->where('conversation_id', $context->conversation->id)
            ->where('seating_area_id', $areaId)
            ->whereDate('reservation_date', $date->toDateString())
            ->where('time_slot', $slot)
            ->where('guests', $guests)
            ->where('guest_name', $guestName)
            ->where('status', Reservation::STATUS_PENDING)
            ->first();
    }

    private function confirmation(ToolContext $context, Reservation $reservation, bool $alreadyRequested = false): ToolResult
    {
        $area = $reservation->seatingArea;

        $payload = [
            'reservation_reference' => $reservation->reference,
            'seating_area_slug' => $area->slug,
            'name' => $area->translatedName($context->locale),
            'date' => $reservation->reservation_date->toDateString(),
            'time_slot' => $reservation->time_slot,
            'time_range' => TableAvailability::slotRange($reservation->time_slot),
            'guests' => $reservation->guests,
            'deposit_total' => (float) $reservation->deposit_total,
            'currency' => $context->cafe->currency,
            'status' => 'pending_confirmation',
        ];

        return ToolResult::json(
            $alreadyRequested ? [...$payload, 'note' => 'This request was already created earlier in this conversation; do not book again.'] : $payload,
            [
                'type' => 'reservation_confirmation',
                'reservation' => $payload,
                'handover' => $this->handover->forReservation($context->cafe, $reservation, $context->locale),
            ],
        );
    }
}
