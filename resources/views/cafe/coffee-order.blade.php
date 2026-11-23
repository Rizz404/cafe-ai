<x-cafe-stage :cafe="$cafe" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :title="__('coffee.title').' · '.$cafe->name">
    <div class="stage-panels">
        <section class="stage-content" aria-label="{{ __('coffee.title') }}">
            <p class="stage-eyebrow">{{ __('coffee.simulation') }}</p>
            <p class="mt-3 text-sm leading-relaxed text-stone-600">{{ __('coffee.demo_note') }}</p>

            @if ($errors->any())
                <div role="alert" class="mt-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @isset($order)
                <div data-coffee-tracker
                    data-status-url="{{ route('coffee-orders.status', ['cafeSlug' => $cafe->slug, 'reference' => $order->reference, 'lang' => $locale]) }}"
                    data-status="{{ $snapshot['status'] }}"
                    data-queued="{{ __('coffee.queued') }}"
                    data-brewing="{{ __('coffee.brewing') }}"
                    data-ready="{{ __('coffee.ready') }}"
                    data-retry="{{ __('coffee.retry') }}"
                    data-expired="{{ __('coffee.expired') }}">
                    <p class="mt-6 text-sm text-stone-500">{{ __('coffee.number') }}</p>
                    <h2>{{ $snapshot['number'] }}</h2>
                    <p class="mt-3 font-semibold">{{ $order->item_name }}</p>
                    <p class="mt-1 text-sm text-stone-600">{{ __('coffee.one_cup') }} · {{ $order->currency }} {{ number_format((float) $order->price, 2, ',', '.') }}</p>

                    <div class="coffee-machine" data-state="{{ $snapshot['status'] }}" style="--coffee-progress: {{ $snapshot['progress'] / 100 }}" aria-hidden="true">
                        <span class="coffee-machine-spout"></span>
                        <span class="coffee-stream"></span>
                        <div class="coffee-cup"><span class="coffee-fill"></span></div>
                        <span class="coffee-saucer"></span>
                    </div>

                    <ol class="mt-5 flex flex-wrap gap-3 text-sm" aria-label="{{ __('coffee.progress') }}">
                        @foreach (['queued', 'brewing', 'ready'] as $step)
                            <li data-coffee-step="{{ $step }}" @if($snapshot['status'] === $step) aria-current="step" @endif class="coffee-step">{{ __('coffee.'.$step) }}</li>
                        @endforeach
                    </ol>
                    <p class="mt-4 font-semibold" data-coffee-status role="status">{{ __('coffee.'.$snapshot['status']) }}</p>
                    <p class="mt-2 text-sm" data-coffee-queue @if($snapshot['status'] !== 'queued') hidden @endif>{{ __('coffee.queue_position') }}: <span data-coffee-position>{{ $snapshot['queue_position'] }}</span></p>
                    <label class="mt-4 block text-sm" for="coffee-progress">{{ __('coffee.progress') }} · <span data-coffee-percent>{{ $snapshot['progress'] }}</span>%</label>
                    <progress id="coffee-progress" class="coffee-progress mt-2 w-full" max="100" value="{{ $snapshot['progress'] }}"></progress>
                    <p class="mt-3 text-sm text-stone-600">{{ __('coffee.duration') }}</p>
                    <p class="mt-3 text-sm" data-coffee-ready @if($snapshot['status'] !== 'ready') hidden @endif>{{ __('coffee.ready_note') }}</p>
                    <p class="mt-3 text-sm text-red-800" data-coffee-error role="alert" hidden></p>
                    <noscript><p class="mt-3 text-sm">{{ __('coffee.retry') }}</p></noscript>
                </div>
            @else
                <h2 class="mt-6!">{{ __('coffee.summary') }}</h2>
                <h3 class="mt-5 text-xl font-semibold">{{ $menuItem->translatedName($locale) }}</h3>
                <p class="mt-2 text-sm text-stone-600">{{ __('coffee.one_cup') }}</p>
                <p class="detail-price mt-4">{{ $cafe->currency }} {{ number_format((float) $menuItem->price, 2, ',', '.') }}</p>
                <p class="mt-3 text-sm text-stone-600">{{ __('coffee.duration') }}</p>
                <form method="POST" action="{{ route('coffee-orders.store', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" class="mt-6" data-coffee-order-form>
                    @csrf
                    <input type="hidden" name="menu_item_id" value="{{ $menuItem->id }}">
                    <input type="hidden" name="request_key" value="{{ old('request_key', $requestKey) }}">
                    <input type="hidden" name="locale" value="{{ $locale }}">
                    <button type="submit" class="stage-action">{{ __('coffee.confirm') }} →</button>
                </form>
            @endisset

            <a href="{{ route('cafe.menu', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" class="detail-scene-step mt-6 inline-flex">← {{ __('coffee.back') }}</a>
        </section>
    </div>
</x-cafe-stage>
