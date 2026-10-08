<x-layouts.admin title="Seating areas">
    <x-slot:actions>
        <x-ui.link-button :href="route('admin.seating-areas.create')" size="sm">Add seating area</x-ui.link-button>
    </x-slot:actions>

    <x-ui.table :columns="['Name', 'Type', 'Capacity', 'Fee', 'Images', 'Status', '']" :empty="$seatingAreas->isEmpty()" empty-text="No seating areas yet.">
        @foreach ($seatingAreas as $seatingArea)
            <tr>
                <td class="px-4 py-3 font-medium text-text">{{ $seatingArea->name }}</td>
                <td class="px-4 py-3 text-muted">{{ \Illuminate\Support\Str::headline($seatingArea->area_type) }}</td>
                <td class="px-4 py-3">{{ $seatingArea->min_guests }}–{{ $seatingArea->max_guests }} guests</td>
                <td class="px-4 py-3">{{ (float) $seatingArea->reservation_fee > 0 ? $cafe->currency.' '.number_format((float) $seatingArea->reservation_fee, 0, ',', '.') : 'Free' }}</td>
                <td class="px-4 py-3">{{ $seatingArea->images_count }}</td>
                <td class="px-4 py-3">
                    <x-ui.badge :tone="$seatingArea->is_active ? 'success' : 'neutral'">{{ $seatingArea->is_active ? 'Active' : 'Hidden' }}</x-ui.badge>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.seating-areas.edit', $seatingArea) }}" class="text-muted hover:underline">Edit</a>
                    <x-ui.confirm-form :action="route('admin.seating-areas.destroy', $seatingArea)" message="Delete this seating area?" confirm-label="Delete" trigger-class="ml-3 text-danger hover:underline">Delete</x-ui.confirm-form>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
</x-layouts.admin>
