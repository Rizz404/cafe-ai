@props(['name' => 'star'])
<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($name)
        @case('wifi')
            <path d="M2.5 8.5a14 14 0 0 1 19 0M5.5 12a9.5 9.5 0 0 1 13 0M8.5 15.5a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1" fill="currentColor"/>
            @break
        @case('power')
            <path d="M9 3v5M15 3v5M6.5 8h11v3a5.5 5.5 0 0 1-11 0V8ZM12 16.5V21"/>
            @break
        @case('workspace')
            <rect x="4" y="5" width="16" height="10" rx="1.5"/><path d="M2 19h20M9 15v4M15 15v4"/>
            @break
        @case('parking')
            <rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9.5 16.5V7.5h3a2.5 2.5 0 0 1 0 5h-3"/>
            @break
        @case('music')
            <path d="M9 18V6l10-2v12"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="16.5" cy="16" r="2.5"/>
            @break
        @case('event')
            <rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4M9 15h6"/>
            @break
        @case('delivery')
            <circle cx="6" cy="17" r="2.5"/><circle cx="18" cy="17" r="2.5"/><path d="M8.5 17h7M6 14.5l2-6h5l2.5 6M13 8.5V6h3"/>
            @break
        @case('pet')
            <circle cx="7" cy="9" r="1.8"/><circle cx="12" cy="6.5" r="1.8"/><circle cx="17" cy="9" r="1.8"/><path d="M12 12c-3 0-5 2.5-5 5 0 1.8 1.6 2.5 5 2.5s5-.7 5-2.5c0-2.5-2-5-5-5Z"/>
            @break
        @case('place')
            <path d="M12 21s-6.5-5.4-6.5-11a6.5 6.5 0 0 1 13 0c0 5.6-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/>
            @break
        @default
            <path d="M5 8h11v5a5 5 0 0 1-5 5H10a5 5 0 0 1-5-5V8ZM16 9.5h1.5a2.5 2.5 0 0 1 0 5H16M8.5 3.5c-.8 1 .8 1.7 0 2.7M12 3.5c-.8 1 .8 1.7 0 2.7"/>
    @endswitch
</svg>
