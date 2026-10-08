<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveCafe;
use App\Http\Requests\Api\QuoteReservationRequest;
use App\Http\Requests\Api\StoreReservationRequest;
use App\Models\Cafe;
use App\Models\Conversation;
use App\Models\SeatingArea;
use App\Modules\Reservation\Actions\CreateReservationRequest;
use App\Modules\Reservation\Actions\ResolveReservationTarget;
use App\Modules\Reservation\Queries\FindAlternatives;
use App\Modules\Reservation\Support\ReservationHandover;
use App\Modules\Reservation\Support\TableAvailability;
use App\Modules\Reservation\Validation\ReservationRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(
        private readonly TableAvailability $availability,
        private readonly ResolveReservationTarget $resolveTarget,
        private readonly FindAlternatives $findAlternatives,
    ) {}

    public function quote(QuoteReservationRequest $request): JsonResponse
    {
        $cafe = ResolveCafe::cafe($request);
        $locale = ResolveCafe::locale($request);

        $data = $request->validated();
        [$area, $date] = $this->resolveTarget->handle($cafe, $data, $locale);

        $quote = $this->availability->quote($area, $date, $data['time_slot']);

        if (! $quote) {
            return $this->unavailable($cafe, $area, $date, $data, $locale);
        }

        return response()->json([
            'available' => true,
            'date' => $quote['date'],
            'time_slot' => $quote['time_slot'],
            'time_range' => $quote['time_range'],
            'available_tables' => $quote['available_tables'],
            'fee' => $quote['fee'],
            'minimum_spend' => $quote['minimum_spend'],
            'currency' => $cafe->currency,
        ]);
    }

    public function store(
        StoreReservationRequest $request,
        CreateReservationRequest $create,
        ReservationHandover $handover,
    ): JsonResponse {
        $cafe = ResolveCafe::cafe($request);
        $locale = ResolveCafe::locale($request);

        $data = $request->validated();
        $isEmail = $request->isEmailContact();

        [$area, $date] = $this->resolveTarget->handle($cafe, $data, $locale);

        $conversation = isset($data['guest_token'])
            ? Conversation::where('cafe_id', $cafe->id)->where('guest_token', $data['guest_token'])->first()
            : null;

        $reservation = $create->handle($cafe, $area, [
            'reservation_date' => $date,
            'time_slot' => $data['time_slot'],
            'guests' => (int) $data['guests'],
            'occasion' => $data['occasion'] ?? null,
            'guest_name' => $data['guest_name'],
            'guest_email' => $isEmail ? $data['contact_value'] : null,
            'guest_phone' => $isEmail ? null : $data['contact_value'],
            'contact_type' => $data['contact_type'],
            'notes' => $data['special_request'] ?? null,
        ], $conversation);

        if (! $reservation) {
            return $this->unavailable($cafe, $area, $date, $data, $locale);
        }

        return response()->json([
            'reference' => $reservation->reference,
            'status' => $reservation->status,
            'fee' => (float) $reservation->deposit_total,
            'currency' => $cafe->currency,
            'handover' => $handover->forReservation($cafe, $reservation, $locale),
        ], 201);
    }

    /**
     * No table is free in that area and slot: say so, and offer what is.
     *
     * @param  array<string, mixed>  $data
     */
    private function unavailable(Cafe $cafe, SeatingArea $requested, CarbonImmutable $date, array $data, string $locale): JsonResponse
    {
        $message = ReservationRules::message('unavailable', $locale);

        return response()->json([
            'message' => $message,
            'errors' => ['seating_area_slug' => [$message]],
            'alternatives' => $this->findAlternatives->handle($cafe, $requested, $date, $data['time_slot'], (int) $data['guests'], $locale),
            'currency' => $cafe->currency,
        ], 422);
    }
}
