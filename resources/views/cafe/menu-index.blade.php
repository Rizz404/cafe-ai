<x-cafe-stage :cafe="$cafe" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :title="$labels['menu_heading'].' · '.$cafe->name">
    <x-slot:welcome>
        <div class="stage-welcome stage-welcome-scene" data-anchor="{{ $backdrop['text'] }}">
            <span class="stage-eyebrow">{{ $stageCopy['explore'] }}</span>
            <h1>{{ $labels['menu_heading'] }}</h1>
            @if ($menuNarrations['menu'])
                @include('cafe.narrator', ['text' => $menuNarrations['menu'], 'key' => 'menu', 'onStage' => true])
            @else
                <p>{{ $stageCopy['menu_empty'] }}</p>
            @endif
        </div>
    </x-slot:welcome>

    @if ($menuItems->isNotEmpty())
        <x-slot:sidebar>@include('cafe.menu-nav')</x-slot:sidebar>
    @endif
</x-cafe-stage>
