{{-- The icon of a board card. Outline strokes on a 24px grid; add a name to the list before using it. --}}
@props(['name'])

@php
    $paths = [
        'cup' => 'M4 8h12v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Zm12 1h2a2 2 0 0 1 0 4h-2M7 3v2m3-2v2m3-2v2',
        'chair' => 'M7 11V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v5M5 11h14v4H5zM7 15v5m10-5v5',
        'sparkle' => 'M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Zm7 12l.7 1.8L21.5 17.5l-1.8.7L19 20l-.7-1.8-1.8-.7 1.8-.7L19 15Z',
        'info' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-10v5m0-8h.01',
        'calendar' => 'M5 5h14v15H5zM5 10h14M9 3v4m6-4v4',
        'chat' => 'M5 5h14v10h-8l-4 4v-4H5z',
    ];
@endphp

<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? '' }}"/></svg>
