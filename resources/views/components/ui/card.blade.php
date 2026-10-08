{{-- Bordered panel. The optional heading renders as the first line; "flush" drops the padding for tables and lists. --}}
@props([
    'heading' => null,
    'flush' => false,
])

<section {{ $attributes->class(['rounded-card border border-border bg-surface', 'p-5' => ! $flush]) }}>
    @if ($heading)
        <h2 class="text-sm font-semibold text-text">{{ $heading }}</h2>
    @endif

    {{ $slot }}
</section>
