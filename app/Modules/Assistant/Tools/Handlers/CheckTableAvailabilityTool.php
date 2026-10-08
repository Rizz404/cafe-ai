<?php

namespace App\Modules\Assistant\Tools\Handlers;

use App\Modules\Assistant\Tools\Support\CatalogSummaries;
use App\Modules\Assistant\Tools\Support\ReservationInput;
use App\Modules\Assistant\Tools\Tool;
use App\Modules\Assistant\Tools\ToolContext;
use App\Modules\Assistant\Tools\ToolResult;
use App\Modules\Reservation\Support\TableAvailability;

final class CheckTableAvailabilityTool implements Tool
{
    public function __construct(
        private readonly TableAvailability $availability,
        private readonly ReservationInput $input,
    ) {}

    public function name(): string
    {
        return 'check_table_availability';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Get the current, real-time availability and exact reservation fee for one seating area on a specific date and time slot. ALWAYS call this before telling a guest a table is available or confirming a fee — never state availability from memory.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'seating_area_slug' => ['type' => 'string'],
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'time_slot' => ['type' => 'string', 'description' => 'Slot start time, HH:MM'],
                        'guests' => ['type' => 'integer', 'minimum' => 1],
                    ],
                    'required' => ['seating_area_slug', 'date', 'time_slot'],
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

        $guests = isset($arguments['guests']) ? (int) $arguments['guests'] : null;

        if ($guests !== null && ! $this->availability->fitsParty($area, $guests)) {
            return new ToolResult("This party size does not suit that seating area (it seats {$area->min_guests}-{$area->max_guests} guests). Suggest another seating area, or call request_human_handover for a large group.");
        }

        $quote = $this->availability->isSlotInPast($cafe, $date, $slot)
            ? null
            : $this->availability->quote($area, $date, $slot);

        if (! $quote) {
            $alternatives = array_map(
                fn (string $s) => ['time_slot' => $s, 'time_range' => TableAvailability::slotRange($s)],
                $this->availability->availableSlots($area, $date),
            );

            return ToolResult::json([
                'available' => false,
                'seating_area_slug' => $area->slug,
                'message' => 'No free table in this seating area for that slot.',
                'other_available_slots_that_day' => $alternatives,
            ], ['type' => 'availability', 'available' => false, 'seating_area_slug' => $area->slug]);
        }

        $result = [
            'available' => true,
            'seating_area_slug' => $area->slug,
            'name' => $area->translatedName($context->locale),
            'date' => $quote['date'],
            'time_slot' => $quote['time_slot'],
            'time_range' => $quote['time_range'],
            'available_tables' => $quote['available_tables'],
            'reservation_fee' => $quote['fee'],
            'minimum_spend' => $quote['minimum_spend'],
            'currency' => $cafe->currency,
        ];

        return ToolResult::json($result, ['type' => 'availability', 'available' => true, 'quote' => $result]);
    }
}
