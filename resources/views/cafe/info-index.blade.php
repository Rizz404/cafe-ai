@php
    $about = $cafe->translatedDescription($locale);
    $mapUrl = $cafe->latitude && $cafe->longitude
        ? 'https://www.google.com/maps?q='.$cafe->latitude.','.$cafe->longitude
        : 'https://www.google.com/maps?q='.rawurlencode(trim($cafe->address.' '.$cafe->city));
    $sections = collect([
        'general' => ['tab' => $stageCopy['info_tab_about'], 'heading' => $stageCopy['info_about']],
        'policies' => ['tab' => $stageCopy['info_policies'], 'heading' => $stageCopy['info_policies']],
        'faq' => ['tab' => $stageCopy['info_tab_faq'], 'heading' => $stageCopy['info_faq']],
    ])->filter(fn ($section, $category) => ($infoItems[$category] ?? collect())->isNotEmpty());
@endphp
<x-cafe-stage :cafe="$cafe" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :nav="false" :title="$labels['info_heading'].' · '.$cafe->name">
    <div class="stage-panels">
        <section class="stage-content info-panel @container" aria-label="{{ $labels['info_heading'] }}">
            <a href="{{ route('cafe.show', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="panel-close" aria-label="{{ $stageCopy['back'] }}"><span aria-hidden="true">×</span></a>
            <p class="stage-eyebrow">{{ $cafe->name }}</p>
            <h2>{{ $labels['info_heading'] }}</h2>
            @include('cafe.narrator', ['text' => $sceneNarrations['info'], 'key' => 'info', 'inline' => true])

            @if ($about)
                <p class="mt-4 leading-relaxed text-stone-600">{{ $about }}</p>
            @endif

            {{-- Opening and closing time, side by side like a receipt stub. --}}
            <div class="info-stay">
                <div class="info-stay-item">
                    <span class="info-stay-label">{{ $stageCopy['opens'] }}</span>
                    <span class="info-stay-time">{{ substr($cafe->opening_time ?? '', 0, 5) ?: '—' }}</span>
                </div>
                <div class="info-stay-divider" aria-hidden="true"></div>
                <div class="info-stay-item">
                    <span class="info-stay-label">{{ $stageCopy['closes'] }}</span>
                    <span class="info-stay-time">{{ substr($cafe->closing_time ?? '', 0, 5) ?: '—' }}</span>
                </div>
            </div>

            <div class="info-facts">
                <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="info-fact">
                    <x-info-icon name="place" />
                    <span>{{ $cafe->city ?: $stageCopy['info_address'] }}</span>
                </a>
                @if ($staffLinks['whatsapp_url'])
                    <a href="{{ $staffLinks['whatsapp_url'] }}" target="_blank" rel="noopener" class="info-fact"><x-info-icon name="contact" /><span>{{ $wizard['whatsapp'] }}</span></a>
                @elseif ($cafe->phone)
                    <a href="{{ $staffLinks['phone_url'] }}" class="info-fact"><x-info-icon name="contact" /><span>{{ $cafe->phone }}</span></a>
                @endif
                @if ($cafe->instagram)
                    <a href="https://instagram.com/{{ ltrim($cafe->instagram, '@') }}" target="_blank" rel="noopener" class="info-fact"><x-info-icon name="about" /><span>{{ $cafe->instagram }}</span></a>
                @endif
            </div>

            <p class="info-address">{{ $cafe->address }}, {{ $cafe->city }}, {{ $cafe->country }}</p>

            @if ($sections->count() > 1)
                <div class="info-tabs">
                    @foreach ($sections as $category => $section)
                        <input type="radio" name="info-tab" id="info-tab-{{ $category }}" class="info-tab-radio" @checked($loop->first)>
                    @endforeach

                    <div class="info-tablist">
                        @foreach ($sections as $category => $section)
                            <label for="info-tab-{{ $category }}">{{ $section['tab'] }}</label>
                        @endforeach
                    </div>

                    <div class="info-tabpanels">
                        @foreach ($sections as $category => $section)
                            <div class="info-tabpanel" data-panel="{{ $category }}">
                                <div class="info-list">
                                    @foreach ($infoItems[$category] as $item)
                                        <details class="info-row">
                                            <summary><span>{{ $item->translatedTitle($locale) }}</span><span aria-hidden="true" class="info-row-chevron">›</span></summary>
                                            <p>{{ $item->translatedBody($locale) }}</p>
                                        </details>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif ($sections->isNotEmpty())
                @php $only = $sections->keys()->first(); @endphp
                <h3 class="info-section-heading">{{ $sections[$only]['heading'] }}</h3>
                <div class="info-list">
                    @foreach ($infoItems[$only] as $item)
                        <details class="info-row">
                            <summary><span>{{ $item->translatedTitle($locale) }}</span><span aria-hidden="true" class="info-row-chevron">›</span></summary>
                            <p>{{ $item->translatedBody($locale) }}</p>
                        </details>
                    @endforeach
                </div>
            @endif

            <button type="button" data-hero-quick-message="{{ $labels['nav_info_q'] }}" class="stage-action mt-8">{{ $stageCopy['info_ask'] }} <span aria-hidden="true">↗</span></button>
        </section>
    </div>
</x-cafe-stage>
