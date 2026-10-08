{{-- On the menu scenes the side card becomes the menu index, so the guest can step between dishes without going back. --}}
<aside class="stage-menu" aria-label="{{ $labels['menu_heading'] }}">
    <a href="{{ route('cafe.show', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="stage-menu-back"><span aria-hidden="true">‹</span> {{ $stageCopy['explore'] }}</a>
    <p class="stage-eyebrow">{{ $labels['menu_heading'] }}</p>
    <nav class="item-nav">
        @foreach ($menuItems->groupBy('category') as $category => $group)
            <p class="item-nav-heading">{{ $terms['category'][$category] ?? Str::headline($category) }}</p>
            @foreach ($group as $item)
                <a href="{{ route('cafe.menu-item', ['cafeSlug' => $cafe->slug, 'itemSlug' => $item->slug, 'lang' => $locale]) }}"
                    data-stage-exit
                    @if (($menuItem ?? null)?->slug === $item->slug) aria-current="page" @endif>
                    @if ($item->image_source)
                        <img class="item-nav-thumb" src="{{ $item->image_source }}" alt="" loading="lazy">
                    @endif
                    <span class="item-nav-text">
                        <span class="item-nav-name">{{ $item->translatedName($locale) }}</span>
                        <span class="item-nav-price">{{ $cafe->currency }} {{ number_format((float) $item->price, 0, ',', '.') }}@if ($item->is_sold_out)<span class="item-nav-badge">{{ $stageCopy['sold_out'] }}</span>@endif</span>
                    </span>
                </a>
            @endforeach
        @endforeach
    </nav>
    <span class="stage-menu-footer">{{ $stageCopy['available'] }} · CAFE AI</span>
</aside>
