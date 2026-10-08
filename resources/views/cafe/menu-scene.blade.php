@php
    $itemName = $menuItem->translatedName($locale);
    $itemUrl = fn ($item) => route('cafe.menu-item', ['cafeSlug' => $cafe->slug, 'itemSlug' => $item->slug, 'lang' => $locale]);
    $knownTags = collect($menuItem->tags ?? [])->filter(fn ($tag) => isset($terms['tag'][$tag]));
@endphp
<section class="stage-content @container" aria-label="{{ $itemName }}">
    <a href="{{ route('cafe.menu', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit class="panel-close" aria-label="{{ $labels['menu_heading'] }}"><span aria-hidden="true">×</span></a>
    <article class="detail-scene">
        <div class="flex flex-wrap items-center justify-between gap-3 pr-12">
            <p class="stage-eyebrow">{{ $stageCopy['item_counter'] }} {{ $itemIndex + 1 }} / {{ $menuItems->count() }}</p>
            @if ($menuItems->count() > 1)
                <nav class="flex gap-2" aria-label="{{ $labels['menu_heading'] }}">
                    <a href="{{ $itemUrl($previousItem) }}" data-stage-exit class="detail-scene-step" rel="prev"><span aria-hidden="true">←</span> {{ $stageCopy['prev_item'] }}</a>
                    <a href="{{ $itemUrl($nextItem) }}" data-stage-exit class="detail-scene-step" rel="next">{{ $stageCopy['next_item'] }} <span aria-hidden="true">→</span></a>
                </nav>
            @endif
        </div>

        @include('cafe.narrator', ['text' => $menuNarrations['item'][$menuItem->slug], 'key' => 'item-'.$menuItem->slug])

        <div class="mt-5 grid gap-7 @2xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <div class="detail-photo">
                @if ($menuItem->image_source)
                    <img src="{{ $menuItem->image_source }}" alt="{{ $itemName }}">
                @else
                    <div class="grid h-full place-items-center text-sm text-stone-500">{{ $stageCopy['no_photo'] }}</div>
                @endif
                @if ($menuItem->is_sold_out)
                    <span class="detail-badge">{{ $stageCopy['sold_out'] }}</span>
                @endif
            </div>

            <div class="min-w-0">
                <h2 class="mt-0!">{{ $itemName }}</h2>
                <p class="mt-3 leading-relaxed text-stone-600">{{ $menuItem->translatedDescription($locale) }}</p>

                <p class="detail-price mt-5">{{ $cafe->currency }} {{ number_format((float) $menuItem->price, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-stone-500">{{ $stageCopy['price_note'] }}</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" class="stage-action" data-hero-quick-message="{{ str_replace(':name', $itemName, $stageCopy['ask_item_q']) }}">{{ $stageCopy['ask_item'] }} <span aria-hidden="true">↗</span></button>
                    <a href="{{ route('cafe.reservation', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_reservation'] }}" class="detail-scene-step">{{ $stageCopy['reserve_table'] }}</a>
                </div>

                <dl class="detail-scene-facts">
                    <div><dt>{{ $stageCopy['category'] }}</dt><dd>{{ $terms['category'][$menuItem->category] ?? Str::headline($menuItem->category) }}</dd></div>
                    @if ($menuItem->serving)
                        <div><dt>{{ $stageCopy['serving'] }}</dt><dd>{{ Str::ucfirst($terms['serving'][$menuItem->serving] ?? Str::headline($menuItem->serving)) }}</dd></div>
                    @endif
                    @if ($menuItem->calories)
                        <div><dt>{{ $stageCopy['calories'] }}</dt><dd>{{ $menuItem->calories }} kcal</dd></div>
                    @endif
                </dl>

                @if ($knownTags->isNotEmpty())
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($knownTags as $tag)
                            <li class="tag-pill {{ in_array($tag, ['signature', 'bestseller'], true) ? 'is-accent' : '' }}">{{ $terms['tag'][$tag] }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (! empty($menuItem->allergens))
                    <h3 class="mt-6 text-sm font-semibold">{{ $stageCopy['contains'] }}</h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($menuItem->allergens as $allergen)
                            <li class="tag-pill is-alert">{{ Str::ucfirst($terms['allergen'][$allergen] ?? $allergen) }}</li>
                        @endforeach
                    </ul>
                @endif
                <p class="detail-note">{{ $stageCopy['allergen_note'] }}</p>
            </div>
        </div>
    </article>
</section>
