<x-layouts.admin title="Reservations">
    <div class="mb-4 flex gap-2 text-sm">
        @foreach (['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'] as $value => $label)
            <a href="{{ route('admin.reservations.index', $value ? ['status' => $value] : []) }}"
                class="rounded-full px-3 py-1 {{ $status === $value || (! $status && $value === '') ? 'bg-primary text-on-primary' : 'border border-border-strong bg-surface text-muted' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-ui.table :columns="['Reference', 'Guest', 'Seating', 'When', 'Fee', 'Status', '']" :empty="$reservations->isEmpty()" empty-text="No reservations found.">
        @foreach ($reservations as $reservation)
            <tr>
                <td class="px-4 py-3 font-mono text-xs">{{ $reservation->reference }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium text-text">{{ $reservation->guest_name }}</p>
                    <p class="text-xs text-muted">{{ $reservation->contact_type ? ucfirst($reservation->contact_type).': ' : '' }}{{ $reservation->guest_phone ?? $reservation->guest_email }}</p>
                    @if ($reservation->notes)<p class="mt-1 max-w-xs text-xs italic text-subtle">{{ $reservation->notes }}</p>@endif
                </td>
                <td class="px-4 py-3">{{ $reservation->seatingArea->name }}<p class="text-xs text-subtle">{{ $reservation->guests }} guests{{ $reservation->occasion ? ' · '.ucfirst($reservation->occasion) : '' }}</p></td>
                <td class="px-4 py-3 text-xs text-muted">{{ $reservation->reservation_date->toFormattedDateString() }}<p>{{ $reservation->time_range }}</p></td>
                <td class="px-4 py-3">{{ (float) $reservation->deposit_total > 0 ? $cafe->currency.' '.number_format((float) $reservation->deposit_total, 0, ',', '.') : 'Free' }}</td>
                <td class="px-4 py-3">
                    <x-ui.badge :tone="match ($reservation->status) { 'pending' => 'warning', 'confirmed' => 'success', default => 'neutral' }">{{ ucfirst($reservation->status) }}</x-ui.badge>
                </td>
                <td class="px-4 py-3 text-right">
                    @if ($reservation->status === 'pending')
                        <form method="POST" action="{{ route('admin.reservations.status', $reservation) }}" class="inline">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="text-success hover:underline">Confirm</button>
                        </form>
                        <x-ui.confirm-form :action="route('admin.reservations.status', $reservation)" method="PATCH" message="Cancel this reservation?" confirm-label="Cancel reservation" cancel-label="Keep" trigger-class="ml-3 text-danger hover:underline">
                            <x-slot:fields>
                                <input type="hidden" name="status" value="cancelled">
                            </x-slot:fields>
                            Cancel
                        </x-ui.confirm-form>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-ui.table>

    <div class="mt-4">{{ $reservations->links() }}</div>
</x-layouts.admin>
