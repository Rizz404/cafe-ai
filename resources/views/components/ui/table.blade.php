{{-- Data table. "columns" are the header labels (use '' for an actions column); the slot holds the <tr> rows. --}}
@props([
    'columns',
    'empty' => false,
    'emptyText' => null,
])

<div class="overflow-hidden rounded-card border border-border bg-surface">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-border bg-surface-muted text-xs uppercase text-muted">
            <tr>
                @foreach ($columns as $column)
                    <th scope="col" class="px-4 py-3">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @if ($empty)
                <tr><td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-subtle">{{ $emptyText }}</td></tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>
