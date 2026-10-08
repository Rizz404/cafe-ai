<x-cafe-stage :cafe="$cafe" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :nav="false" :title="$labels['staff_heading'].' · '.$cafe->name">
    <div class="stage-panels">
        <section class="stage-content staff-panel @container" aria-label="{{ $labels['staff_heading'] }}">
            <a href="{{ route('cafe.show', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="panel-close" aria-label="{{ $stageCopy['back'] }}"><span aria-hidden="true">×</span></a>
            <p class="stage-eyebrow">{{ $cafe->name }}</p>
            <h2>{{ $labels['staff_heading'] }}</h2>
            @include('cafe.narrator', ['text' => $stageCopy['staff_intro'], 'key' => 'staff', 'inline' => true])

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="button" data-hero-quick-message="{{ $labels['nav_staff_q'] }}" class="stage-action">{{ $labels['staff_heading'] }} <span aria-hidden="true">↗</span></button>
                @if($staffLinks['whatsapp_url'])<a href="{{ $staffLinks['whatsapp_url'] }}" target="_blank" rel="noopener" class="wizard-secondary">{{ $wizard['whatsapp'] }}</a>@endif
                @if($staffLinks['phone_url'])<a href="{{ $staffLinks['phone_url'] }}" class="wizard-secondary">{{ $wizard['call_cafe'] }}</a>@endif
                @if($staffLinks['email_url'])<a href="{{ $staffLinks['email_url'] }}" class="wizard-secondary">{{ $wizard['email_cafe'] }}</a>@endif
            </div>

            <div class="staff-location">
                <h3>{{ $stageCopy['location'] }}</h3>
                <p>{{ $cafe->address }}, {{ $cafe->city }}, {{ $cafe->country }}</p>
                @if($cafe->phone)<p>{{ $cafe->phone }}</p>@endif
                @if($cafe->email)<p>{{ $cafe->email }}</p>@endif
                @if($cafe->instagram)<p>{{ $cafe->instagram }}</p>@endif
            </div>
        </section>
    </div>
</x-cafe-stage>
