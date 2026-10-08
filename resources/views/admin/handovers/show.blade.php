<x-layouts.admin title="Handover detail">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">
        <x-ui.card flush>
            <div class="border-b border-border px-4 py-3">
                <p class="font-medium text-text">Conversation</p>
                <p class="text-xs text-muted">{{ $handover->conversation->guest_name ?? 'Guest' }} · {{ strtoupper($handover->conversation->locale) }}</p>
            </div>
            <div class="max-h-130 space-y-3 overflow-y-auto px-4 py-4">
                @foreach ($handover->conversation->messages as $message)
                    <div class="flex {{ $message->role === 'guest' ? 'justify-end' : 'justify-start' }}">
                        <div @class([
                            'max-w-[80%] rounded-2xl px-3 py-2 text-sm',
                            'bg-primary text-on-primary' => $message->role === 'guest',
                            'bg-surface-muted text-text' => $message->role === 'assistant',
                            'border border-border-strong bg-surface text-text' => $message->role === 'staff',
                            'border border-warning-border bg-warning-soft text-xs text-warning' => $message->role === 'system',
                        ])>
                            @if ($message->role === 'staff')
                                <p class="mb-0.5 text-[10px] font-semibold uppercase tracking-wide text-muted">Team</p>
                            @endif
                            {{ $message->content }}
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($handover->status === 'open')
                <form method="POST" action="{{ route('admin.handovers.reply', $handover) }}" class="flex items-center gap-2 border-t border-border p-3">
                    @csrf
                    <input type="text" name="message" required aria-label="Reply to the guest" placeholder="Reply to the guest…" class="flex-1 rounded-control border border-border-strong bg-surface px-3 py-2 text-sm text-text placeholder:text-subtle">
                    <x-ui.button type="submit">Send</x-ui.button>
                </form>
            @endif
        </x-ui.card>

        <div class="space-y-4">
            <div class="rounded-card border border-warning-border bg-warning-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-warning">{{ ucwords(str_replace('_', ' ', $handover->reason)) }}</p>
                <p class="mt-2 text-sm text-warning">{{ $handover->summary }}</p>
            </div>

            @if ($handover->status === 'open')
                <form method="POST" action="{{ route('admin.handovers.resolve', $handover) }}">
                    @csrf
                    <x-ui.button type="submit" class="w-full">Mark resolved — return to the AI Barista</x-ui.button>
                </form>
            @else
                <p class="rounded-control border border-border bg-surface px-4 py-2 text-center text-sm text-muted">Resolved {{ $handover->resolved_at?->diffForHumans() }}</p>
            @endif
        </div>
    </div>
</x-layouts.admin>
