<?php

namespace App\Modules\Assistant\Prompt;

use App\Models\Cafe;
use App\Models\Conversation;
use App\Modules\Conversation\Support\ContentGuard;
use App\Modules\Reservation\Support\TableAvailability;

/**
 * Assembles what the model is told on every turn: the system prompt with its
 * hard rules, what the guest is looking at on screen, and the scope reminder
 * that is repeated right after the guest's latest message. The wording itself
 * lives in Templates/*.json (see PromptLibrary); this class only fills in the
 * live values.
 */
class PromptComposer
{
    public function __construct(
        private readonly ContentGuard $guard,
        private readonly TableAvailability $availability,
        private readonly PromptLibrary $library,
    ) {}

    public function systemPrompt(Cafe $cafe, Conversation $conversation): string
    {
        $system = $this->library->load('system');
        $variables = [
            'cafe_name' => $cafe->name,
            'city' => (string) $cafe->city,
            'country' => (string) $cafe->country,
            'locale_name' => $system['languages'][$conversation->locale] ?? $system['fallback_language'],
            'currency' => (string) $cafe->currency,
            'today' => now($cafe->timezone)->toDateString(),
            'opening' => substr((string) $cafe->opening_time, 0, 5).'–'.substr((string) $cafe->closing_time, 0, 5),
            'slots' => collect($this->availability->slotsFor($cafe))
                ->map(fn (string $slot) => TableAvailability::slotRange($slot))
                ->implode(', '),
        ];

        $rules = collect($system['rules'])
            ->map(fn (string $rule, int $index) => ($index + 1).'. '.$this->library->render($rule, $variables))
            ->implode("\n");

        return implode("\n\n", [
            $this->library->render($system['intro'], $variables),
            $system['rules_heading']."\n".$rules,
            $this->library->render($system['facts'], $variables),
            $this->uiContext($conversation),
        ]);
    }

    /**
     * Repeats the scope rule right after the guest's latest message, where the
     * model weighs it most. Only the request carries it; it is never stored.
     *
     * @param  list<array{role: string, content: string}>  $history
     * @param  string  $fallbackLocale  the page language, used when the message has no clear one
     * @return list<array{role: string, content: string}>
     */
    public function withScopeReminder(array $history, Cafe $cafe, string $fallbackLocale): array
    {
        $last = array_key_last($history);

        if ($last === null || $history[$last]['role'] !== 'user') {
            return $history;
        }

        $reminder = $this->library->load('reminder');
        $language = $reminder['languages'][$this->guard->detectLocale($history[$last]['content'], $fallbackLocale)];

        $history[$last]['content'] .= "\n\n".$this->library->render($reminder['scope'], [
            'language' => $language,
            'cafe_name' => $cafe->name,
        ]);

        return $history;
    }

    /**
     * Tells the model what the guest is looking at, per the scene-aware rules
     * of the product spec. Names come from the database, never the guest.
     */
    private function uiContext(Conversation $conversation): string
    {
        $scene = in_array($conversation->current_scene, Conversation::SCENES, true) ? $conversation->current_scene : 'home';
        $menuItem = $conversation->selectedMenuItem;
        $area = $conversation->selectedSeatingArea;

        $scenes = $this->library->load('scenes');
        $labels = $scenes['labels'];
        $none = $labels['none'];

        $lines = [
            $labels['title'],
            $this->library->render($labels['scene'], ['scene' => $scene]),
            $this->library->render($labels['menu_item'], ['value' => $menuItem ? "{$menuItem->name} (slug: {$menuItem->slug})" : $none]),
            $this->library->render($labels['seating_area'], ['value' => $area ? "{$area->name} (slug: {$area->slug})" : $none]),
            $this->library->render($labels['facility'], ['value' => $conversation->selectedFacility?->title ?? $none]),
        ];

        $draft = $conversation->reservation_state;

        if (is_array($draft) && $draft !== []) {
            $lines[] = $this->library->render($labels['reservation_draft'], [
                'value' => collect($draft)->map(fn ($value, $key) => "{$key}={$value}")->implode(', '),
            ]);
        }

        $lines[] = $this->library->render($labels['guidance'], ['value' => $scenes['guidance'][$scene]]);

        return implode("\n", $lines);
    }
}
