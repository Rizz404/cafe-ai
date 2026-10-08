<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Models\Reservation;
use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Support\ReservationInput;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;
use App\Modules\Reservation\Actions\CreateReservationRequest;
use App\Modules\Reservation\Support\TableAvailability;

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
                'description' => 'Create a table reservation request after the guest confirms the seating area, date, time slot and party size and you have their name and phone. This re-checks availability before booking. It does not charge payment — it creates a pending reservation for the cafe to confirm.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'seating_area_slug' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'time_slot' => ['type' => 'string', 'description' => 'Slot start time, HH:MM'],
                        'guests' => ['type' => 'integer', 'minimum' => 1],
                        'occasion' => ['type' => 'string', 'enum' => Reservation::OCCASIONS],
                        'guest_name' => ['type' => 'string'],
                        'guest_email' => ['type' => 'string'],
                        'guest_phone' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                    ],
                    'required' => ['seating_area_slug', 'date', 'time_slot', 'guests', 'guest_name', 'guest_phone'],
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

        $guests = (int) ($arguments['guests'] ?? 0);

        if (! $this->availability->fitsParty($area, $guests)) {
            return new ToolResult("This party size does not suit that seating area (it seats {$area->min_guests}-{$area->max_guests} guests). Suggest another seating area, or call request_human_handover for a large group.");
        }

        if ($this->availability->isSlotInPast($cafe, $date, $slot)) {
            return new ToolResult('That time slot has already started. Ask the guest for a later slot.');
        }

        $occasion = in_array($arguments['occasion'] ?? null, Reservation::OCCASIONS, true) ? $arguments['occasion'] : null;

        $reservation = $this->create->handle($cafe, $area, [
            'reservation_date' => $date,
            'time_slot' => $slot,
            'guests' => $guests,
            'occasion' => $occasion,
            'guest_name' => $arguments['guest_name'],
            'guest_email' => $arguments['guest_email'] ?? null,
            'guest_phone' => $arguments['guest_phone'],
            'notes' => $arguments['notes'] ?? null,
        ], $context->conversation);

        if (! $reservation) {
            return new ToolResult('No table is free in that slot any more — availability may have just changed. Call check_table_availability again or offer another slot.');
        }

        $payload = [
            'reservation_reference' => $reservation->reference,
            'seating_area_slug' => $area->slug,
            'name' => $area->translatedName($context->locale),
            'date' => $date->toDateString(),
            'time_slot' => $slot,
            'time_range' => TableAvailability::slotRange($slot),
            'guests' => $guests,
            'deposit_total' => (float) $reservation->deposit_total,
            'currency' => $cafe->currency,
            'status' => 'pending_confirmation',
        ];

        return ToolResult::json($payload, ['type' => 'reservation_confirmation', 'reservation' => $payload]);
    }
}
