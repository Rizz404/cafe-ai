<section class="stage-content stage-panel-right @container" aria-label="{{ $wizard['title'] }}">
    <a href="{{ route('cafe.show', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="panel-close" aria-label="{{ $stageCopy['back'] }}"><span aria-hidden="true">×</span></a>
    <p class="stage-eyebrow">{{ $cafe->name }}</p>
    <h2>{{ $wizard['title'] }}</h2>

    <div class="wizard" data-wizard
        data-quote-url="{{ route('reservation.quote', $cafe->slug) }}"
        data-submit-url="{{ route('reservation.store', $cafe->slug) }}"
        data-cafe-slug="{{ $cafe->slug }}"
        data-locale="{{ $locale }}"
        data-currency="{{ $cafe->currency }}"
        data-today="{{ $today }}"
        data-max-days="{{ $maxAdvanceDays }}"
        data-preselect-area="{{ $preselectedArea ?? '' }}"
    >
        <div data-wizard-flow>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-stone-600">{{ $wizard['intro'] }}</p>

            <div class="wizard-progress" aria-label="{{ $wizard['title'] }}">
                <p class="wizard-step-label" data-wizard-step-label aria-live="polite"></p>
                <ol>
                    @foreach ($wizard['steps'] as $stepLabel)
                        <li data-progress-step><span>{{ $stepLabel }}</span></li>
                    @endforeach
                </ol>
            </div>

            <form data-wizard-form novalidate autocomplete="on">
                <div data-step="1" class="wizard-step">
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['date'] }}</span>
                        <input type="date" name="reservation_date" min="{{ $today }}" max="{{ now($cafe->timezone)->addDays($maxAdvanceDays)->toDateString() }}" required>
                    </label>
                    <fieldset class="wizard-field-wide">
                        <legend>{{ $wizard['time'] }}</legend>
                        <div class="wizard-slots">
                            @foreach ($slots as $slot)
                                <label><input type="radio" name="time_slot" value="{{ $slot['value'] }}"><span>{{ $slot['label'] }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                    <p class="wizard-hint">{{ $wizard['slot_hint'] }}</p>
                </div>

                <div data-step="2" class="wizard-step" hidden>
                    <label class="wizard-field">
                        <span>{{ $wizard['guests'] }}</span>
                        <input type="number" name="guests" min="1" max="30" value="2" inputmode="numeric" required>
                    </label>
                    <label class="wizard-field">
                        <span>{{ $wizard['occasion'] }}</span>
                        <select name="occasion" class="wizard-select">
                            <option value="">{{ $wizard['occasion_none'] }}</option>
                            @foreach ($terms['occasion'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div data-step="3" class="wizard-step wizard-step-wide" hidden>
                    <fieldset>
                        <legend>{{ $wizard['choose_area'] }}</legend>
                        <div class="wizard-seats">
                            @foreach ($seatingAreas as $area)
                                <label class="wizard-seat" data-area-option
                                    data-name="{{ $area->translatedName($locale) }}"
                                    data-min-guests="{{ $area->min_guests }}"
                                    data-max-guests="{{ $area->max_guests }}"
                                >
                                    <input type="radio" name="seating_area_slug" value="{{ $area->slug }}">
                                    <span class="wizard-seat-body">
                                        <strong>{{ $area->translatedName($locale) }}</strong>
                                        <span>{{ $terms['area_type'][$area->area_type] ?? Str::headline($area->area_type) }}</span>
                                        <span>{{ $area->min_guests > 1 ? str_replace([':min', ':max'], [(string) $area->min_guests, (string) $area->max_guests], $wizard['fits']) : str_replace(':max', (string) $area->max_guests, $wizard['fits_one']) }}</span>
                                        <span class="wizard-seat-price">@if ((float) $area->reservation_fee > 0){{ $wizard['fee_label'] }}: {{ $cafe->currency }} {{ number_format((float) $area->reservation_fee, 0, ',', '.') }}@else{{ $wizard['fee_label'] }}: {{ $wizard['fee_free'] }}@endif</span>
                                        <span class="wizard-seat-hint" data-area-hint></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div data-step="4" class="wizard-step" hidden>
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['name'] }}</span>
                        <input type="text" name="guest_name" maxlength="100" autocomplete="name" required>
                    </label>
                    <fieldset class="wizard-field-wide">
                        <legend>{{ $wizard['contact_method'] }}</legend>
                        <div class="wizard-pills">
                            @foreach (['whatsapp', 'phone', 'email'] as $method)
                                <label><input type="radio" name="contact_type" value="{{ $method }}" @checked($method === 'whatsapp')><span>{{ $wizard[$method] }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['contact_value'] }}</span>
                        <input type="text" name="contact_value" maxlength="120" autocomplete="tel" required>
                    </label>
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['special'] }}</span>
                        <textarea name="special_request" rows="2" maxlength="500" placeholder="{{ $wizard['special_placeholder'] }}"></textarea>
                    </label>
                </div>

                <div data-step="5" class="wizard-step wizard-step-wide" hidden>
                    <dl class="wizard-summary" data-summary></dl>
                    <p class="wizard-total"><span>{{ $wizard['fee_label'] }}</span> <strong data-summary-total></strong></p>
                    <p class="wizard-hint" data-summary-spend hidden></p>
                    <p class="wizard-hint">{{ $wizard['disclaimer'] }}</p>
                </div>

                <div class="wizard-error" data-wizard-error role="alert" hidden>
                    <p data-wizard-error-text></p>
                    <div class="wizard-alternatives" data-wizard-alternatives hidden>
                        <p>{{ $wizard['available_instead'] }}</p>
                        <ul></ul>
                    </div>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="wizard-secondary" data-wizard-back hidden>{{ $wizard['back'] }}</button>
                    <button type="button" class="stage-action" data-wizard-next>{{ $wizard['next'] }} <span aria-hidden="true">→</span></button>
                    <button type="submit" class="stage-action" data-wizard-submit hidden>{{ $wizard['submit'] }}</button>
                </div>
            </form>
        </div>

        <div class="wizard-done" data-wizard-done hidden>
            <p class="stage-eyebrow">{{ $wizard['done_title'] }}</p>
            <p class="wizard-reference-label">{{ $wizard['reference'] }}</p>
            <p class="wizard-reference" data-done-reference></p>
            <p class="wizard-status">{{ $wizard['awaiting'] }}</p>
            <p class="mt-4 max-w-md text-sm leading-relaxed text-stone-600">{{ $wizard['done_hint'] }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a class="stage-action" data-done-whatsapp target="_blank" rel="noopener" hidden>{{ $wizard['send_whatsapp'] }} <span aria-hidden="true">↗</span></a>
                <a class="wizard-secondary" data-done-phone hidden>{{ $wizard['call_cafe'] }}</a>
                <a class="wizard-secondary" data-done-email hidden>{{ $wizard['email_cafe'] }}</a>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('cafe.show', ['cafeSlug' => $cafe->slug, 'lang' => $locale]) }}" data-stage-exit class="wizard-secondary">{{ $stageCopy['back'] }}</a>
                <button type="button" class="wizard-secondary" data-wizard-reset>{{ $wizard['new_request'] }}</button>
            </div>
        </div>

        <script type="application/json" data-wizard-labels>@json($wizard)</script>
    </div>
</section>
