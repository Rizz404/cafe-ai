<x-layouts.admin title="Handovers">
    <div class="mb-4 flex gap-2 text-sm">
        @foreach (['open' => 'Open', 'resolved' => 'Resolved'] as $value => $label)
            <a href="{{ route('admin.handovers.index', ['status' => $value]) }}"
                class="rounded-full px-3 py-1 {{ $status === $value ? 'bg-primary text-on-primary' : 'border border-border-strong bg-surface text-muted' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($handovers as $handover)
            <a href="{{ route('admin.handovers.show', $handover) }}" class="block rounded-card border border-border bg-surface p-4 hover:border-border-strong">
                <div class="flex items-center justify-between">
                    <p class="font-medium text-text">{{ ucwords(str_replace('_', ' ', $handover->reason)) }}</p>
                    <p class="text-xs text-subtle">{{ $handover->created_at->diffForHumans() }}</p>
                </div>
                <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $handover->summary }}</p>
            </a>
        @empty
            <p class="rounded-card border border-border bg-surface px-4 py-8 text-center text-subtle">Nothing here.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $handovers->links() }}</div>
</x-layouts.admin>
