{{-- Outline icons (24px, Heroicons paths, MIT). Add a name to the whitelist below before using it. --}}
@props([
    'name',
    'label' => null,
])

@php
    $paths = [
        'bars-3' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
        'x-mark' => 'M6 18 18 6M6 6l12 12',
        'plus' => 'M12 4.5v15m7.5-7.5h-15',
    ];
@endphp

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    stroke-linecap="round"
    stroke-linejoin="round"
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    {{ $attributes->class('size-5 shrink-0') }}
>
    <path d="{{ $paths[$name] ?? '' }}" />
</svg>
