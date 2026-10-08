{{--
    A form whose submit button first asks for confirmation in a dialog. The slot
    is the visible trigger label; "method" is the spoofed HTTP verb.
--}}
@props([
    'action',
    'message',
    'confirmLabel',
    'cancelLabel' => 'Cancel',
    'method' => 'DELETE',
    'variant' => 'danger',
    'triggerClass' => 'text-danger hover:underline',
])

<form method="POST" action="{{ $action }}" x-data="confirmForm" {{ $attributes->class('inline') }}>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    {{ $fields ?? '' }}

    <button type="button" x-on:click="open()" class="{{ $triggerClass }}">{{ $slot }}</button>

    <div x-show="isOpen" x-cloak x-on:keydown.escape.window="close()" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-overlay" x-on:click="close()" aria-hidden="true"></div>
        <div role="alertdialog" aria-modal="true" class="relative w-full max-w-sm rounded-card bg-surface p-5 text-left shadow-xl">
            <p class="text-sm text-text">{{ $message }}</p>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button variant="secondary" size="sm" x-on:click="close()">{{ $cancelLabel }}</x-ui.button>
                <x-ui.button :variant="$variant" size="sm" type="submit">{{ $confirmLabel }}</x-ui.button>
            </div>
        </div>
    </div>
</form>
