{{-- A single centred card, for sign-in. --}}
@props([
    'title' => null,
])

<x-layouts.base :title="$title" class="flex items-center justify-center">
    <main id="main-content" tabindex="-1" class="w-full max-w-sm p-4 focus:outline-none">
        <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
            {{ $slot }}
        </div>
    </main>
</x-layouts.base>
