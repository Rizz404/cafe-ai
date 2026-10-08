@props(['name'])
<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($name)
        @case('about')
            <rect x="5" y="3" width="14" height="18" rx="1.2"/><path d="M9 21v-4.5h6V21M9 7.5h1M14 7.5h1M9 11.5h1M14 11.5h1"/>
            @break
        @case('policies')
            <path d="M12 3.5 19 6v5.5c0 4.6-3 8.2-7 9.3-4-1.1-7-4.7-7-9.3V6l7-2.5Z"/><path d="m9.2 12 1.9 1.9 3.7-3.8"/>
            @break
        @case('faq')
            <circle cx="12" cy="12" r="8.5"/><path d="M9.3 9.3a2.7 2.7 0 0 1 5.1-1.1c0 1.9-2.4 2.1-2.4 3.8"/><path d="M12 16.2h.01"/>
            @break
        @case('place')
            <path d="M12 21s-6.5-5.4-6.5-11a6.5 6.5 0 0 1 13 0c0 5.6-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>
            @break
        @case('contact')
            <path d="M4.5 5.5c0-1 .8-1.5 1.7-1.5h1.6c.6 0 1.1.4 1.3 1l1 2.6c.2.5 0 1.1-.4 1.5l-1.3 1.2a13 13 0 0 0 5.8 5.8l1.2-1.3c.4-.4 1-.5 1.5-.4l2.6 1c.6.2 1 .7 1 1.3v1.6c0 .9-.5 1.7-1.5 1.7C11.5 20 4 12.5 4.5 5.5Z"/>
            @break
        @default
            <path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/>
    @endswitch
</svg>
