{{-- The AI Barista "speaking" a short intro for the scene. $text comes from stored cafe data. --}}
<div class="narrator @if($onStage ?? false) narrator-on-stage @endif @if($inline ?? false) narrator-inline @endif" data-narrator data-key="{{ $key }}" data-lang="{{ ['id' => 'id-ID', 'en' => 'en-US', 'ja' => 'ja-JP'][$locale] }}" data-voice-preference="female" data-stop-label="{{ $narration['stop'] }}" data-listen-label="{{ $narration['listen'] }}">
    <span class="narrator-avatar" aria-hidden="true"></span>
    <div class="narrator-bubble">
        <p class="narrator-name">
            {{ $labels['chat_heading'] }}
            <span class="narrator-wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
        </p>
        <p class="sr-only">{{ $text }}</p>
        <p class="narrator-text" data-narrator-text data-text="{{ $text }}" aria-hidden="true">{{ $text }}</p>
        <div class="narrator-actions">
            <button type="button" data-narrator-listen hidden>{{ $narration['listen'] }}</button>
            <button type="button" data-narrator-skip>{{ $narration['skip'] }}</button>
        </div>
    </div>
</div>
