<x-cafe-stage :cafe="$cafe" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :title="$labels['seating_heading'].' · '.$cafe->name">
    <x-slot:welcome>
        <div class="stage-welcome stage-welcome-scene" data-anchor="{{ $backdrop['text'] }}">
            <span class="stage-eyebrow">{{ $stageCopy['explore'] }}</span>
            <h1>{{ $labels['seating_heading'] }}</h1>
            @if ($seatingNarrations['seating'])
                @include('cafe.narrator', ['text' => $seatingNarrations['seating'], 'key' => 'seating', 'onStage' => true])
            @else
                <p>{{ $stageCopy['seating_empty'] }}</p>
            @endif
        </div>
    </x-slot:welcome>

    @if ($seatingAreas->isNotEmpty())
        <x-slot:sidebar>@include('cafe.seating-nav')</x-slot:sidebar>
    @endif
</x-cafe-stage>
