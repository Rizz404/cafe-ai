{{-- On the seating scenes the side card becomes the area index, so the guest can step between areas without going back. --}}
<aside class="stage-menu" aria-label="{{ $labels['seating_heading'] }}">
    <a href="{{ route('cafe.show', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="stage-menu-back"><span aria-hidden="true">‹</span> {{ $stageCopy['explore'] }}</a>
    <p class="stage-eyebrow">{{ $labels['seating_heading'] }}</p>
    <nav class="item-nav">
        @foreach ($seatingAreas as $item)
            <a href="{{ route('cafe.seating-area', ['cafeSlug' => $cafe->slug, 'areaSlug' => $item->slug, 'lang' => $locale]) }}"
                data-stage-exit
                @if (($seatingArea ?? null)?->slug === $item->slug) aria-current="page" @endif>
                @if ($item->images->first())
                    <img class="item-nav-thumb" src="{{ $item->images->first()->image_source }}" alt="" loading="lazy">
                @endif
                <span class="item-nav-text">
                    <span class="item-nav-name">{{ $item->translatedName($locale) }}</span>
                    <span class="item-nav-price">{{ $terms['area_type'][$item->area_type] ?? Str::headline($item->area_type) }} · {{ $item->min_guests > 1 ? $item->min_guests.'–' : '' }}{{ $item->max_guests }} {{ $stageCopy['guests_unit'] }}</span>
                </span>
            </a>
        @endforeach
    </nav>
    <span class="stage-menu-footer">{{ $stageCopy['available'] }} · CAFE AI</span>
</aside>
