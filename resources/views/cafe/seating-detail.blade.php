<x-cafe-stage :cafe="$cafe" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :title="$seatingArea->translatedName($locale).' · '.$cafe->name">
    @if($seatingAreas->isNotEmpty())
        <x-slot:sidebar>@include('cafe.seating-nav')</x-slot:sidebar>
    @endif

    <div class="stage-panels">
        @include('cafe.seating-scene')
    </div>
</x-cafe-stage>
