@php
    $areaName = $seatingArea->translatedName($locale);
    $primaryImage = $seatingArea->images->first();
    $areaUrl = fn ($area) => route('cafe.seating-area', ['cafeSlug' => $cafe->slug, 'areaSlug' => $area->slug, 'lang' => $locale]);
    $source = fn ($image) => $image->image_source;
    $capacity = ($seatingArea->min_guests > 1 ? $seatingArea->min_guests.'–' : '').$seatingArea->max_guests.' '.$stageCopy['guests_unit'];
@endphp
<section class="stage-content @container" aria-label="{{ $areaName }}">
    <a href="{{ route('cafe.seating', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit class="panel-close" aria-label="{{ $labels['seating_heading'] }}"><span aria-hidden="true">×</span></a>
    <article class="detail-scene">
        <div class="flex flex-wrap items-center justify-between gap-3 pr-12">
            <p class="stage-eyebrow">{{ $stageCopy['area_counter'] }} {{ $areaIndex + 1 }} / {{ $seatingAreas->count() }}</p>
            @if ($seatingAreas->count() > 1)
                <nav class="flex gap-2" aria-label="{{ $labels['seating_heading'] }}">
                    <a href="{{ $areaUrl($previousArea) }}" data-stage-exit class="detail-scene-step" rel="prev"><span aria-hidden="true">←</span> {{ $stageCopy['prev_area'] }}</a>
                    <a href="{{ $areaUrl($nextArea) }}" data-stage-exit class="detail-scene-step" rel="next">{{ $stageCopy['next_area'] }} <span aria-hidden="true">→</span></a>
                </nav>
            @endif
        </div>

        @include('cafe.narrator', ['text' => $seatingNarrations['area'][$seatingArea->slug], 'key' => 'area-'.$seatingArea->slug])

        <div class="mt-5 grid gap-7 @2xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <div data-detail-gallery>
                <div class="detail-photo">
                    @if ($primaryImage)
                        <img src="{{ $source($primaryImage) }}" alt="{{ $primaryImage->alt_text }}" data-detail-gallery-main>
                    @else
                        <div class="grid h-full place-items-center text-sm text-stone-500">{{ $stageCopy['no_photo'] }}</div>
                    @endif
                </div>
                @if ($seatingArea->images->count() > 1)
                    <ul class="mt-3 grid grid-cols-4 gap-2" aria-label="{{ $stageCopy['gallery'] }}">
                        @foreach ($seatingArea->images as $imageIndex => $image)
                            <li>
                                <button type="button" class="detail-scene-thumb" data-detail-thumb data-src="{{ $source($image) }}" data-alt="{{ $image->alt_text }}" aria-label="{{ $stageCopy['photo'] }} {{ $imageIndex + 1 }}" @if($imageIndex === 0) aria-current="true" @endif>
                                    <img src="{{ $source($image) }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="min-w-0">
                <h2 class="mt-0!">{{ $areaName }}</h2>
                <p class="mt-3 leading-relaxed text-stone-600">{{ $seatingArea->translatedDescription($locale) }}</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('cafe.reservation', ['cafeSlug' => $cafe->slug, 'lang' => $locale, 'area' => $seatingArea->slug]) }}" data-stage-exit data-tour-line="{{ $narration['tour_reservation'] }}" class="stage-action">{{ $stageCopy['reserve_area'] }} <span aria-hidden="true">↗</span></a>
                    <button type="button" class="detail-scene-step" data-hero-quick-message="{{ str_replace(':name', $areaName, $stageCopy['ask_area_q']) }}">{{ $stageCopy['ask_area'] }}</button>
                </div>
                <p class="mt-3 text-xs text-stone-500">{{ $stageCopy['availability_note'] }}</p>

                <dl class="detail-scene-facts">
                    <div><dt>{{ $stageCopy['type'] }}</dt><dd>{{ $terms['area_type'][$seatingArea->area_type] ?? Str::headline($seatingArea->area_type) }}</dd></div>
                    <div><dt>{{ $stageCopy['capacity'] }}</dt><dd>{{ $capacity }}</dd></div>
                    <div>
                        <dt>{{ $stageCopy['fee'] }}</dt>
                        <dd>@if ((float) $seatingArea->reservation_fee > 0){{ $cafe->currency }} {{ number_format((float) $seatingArea->reservation_fee, 0, ',', '.') }} {{ $stageCopy['per_table'] }}@else{{ $stageCopy['free'] }}@endif</dd>
                    </div>
                    @if ($seatingArea->minimum_spend && (float) $seatingArea->minimum_spend > 0)
                        <div><dt>{{ $stageCopy['min_spend'] }}</dt><dd>{{ $cafe->currency }} {{ number_format((float) $seatingArea->minimum_spend, 0, ',', '.') }}</dd></div>
                    @endif
                </dl>

                @if (! empty($seatingArea->features))
                    <h3 class="mt-6 text-sm font-semibold">{{ $stageCopy['features'] }}</h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($seatingArea->features as $feature)
                            <li class="tag-pill">{{ $terms['feature'][$feature] ?? Str::headline($feature) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </article>
</section>
