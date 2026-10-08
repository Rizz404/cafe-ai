@props(['on', 'off'])
<button type="button" class="sound-toggle" data-sound-toggle data-label-on="{{ $on }}" data-label-off="{{ $off }}" aria-pressed="true" aria-label="{{ $on }}">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 9.5v5h3.5L12 18.5v-13L7.5 9.5H4Z" fill="currentColor" stroke="none"/>
        <path class="sound-waves" d="M15.5 9a4 4 0 0 1 0 6M18 6.5a7.5 7.5 0 0 1 0 11"/>
        <path class="sound-mute" d="m16 9.5 5 5m0-5-5 5"/>
    </svg>
</button>
