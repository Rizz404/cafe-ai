<x-cafe-stage :cafe="$cafe" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :title="$menuItem->translatedName($locale).' · '.$cafe->name">
    @if($menuItems->isNotEmpty())
        <x-slot:sidebar>@include('cafe.menu-nav')</x-slot:sidebar>
    @endif

    <div class="stage-panels">
        @include('cafe.menu-scene')
    </div>
</x-cafe-stage>
