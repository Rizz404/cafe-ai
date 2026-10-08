{{-- The admin menu. "items" is a list of ['route' => ..., 'label' => ...]; the current section is highlighted. --}}
@props([
    'items',
])

<nav class="space-y-1 text-sm" aria-label="Admin">
    @foreach ($items as $item)
        @php
            $section = str_ends_with($item['route'], '.index') ? substr($item['route'], 0, -5) : $item['route'];
            $current = request()->routeIs($section.'*');
        @endphp
        <a
            href="{{ route($item['route']) }}"
            @if ($current) aria-current="page" @endif
            class="block rounded-control px-3 py-2 {{ $current ? 'bg-primary text-on-primary' : 'text-muted hover:bg-surface-hover' }}"
        >{{ $item['label'] }}</a>
    @endforeach
</nav>
