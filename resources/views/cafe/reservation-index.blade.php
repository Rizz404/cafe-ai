<x-cafe-stage :cafe="$cafe" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :nav="false" :title="$labels['reservation_heading'].' · '.$cafe->name">
    <div class="stage-panels">
        @include('cafe.reservation')
    </div>
</x-cafe-stage>
