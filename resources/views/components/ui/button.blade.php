@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

<button type="{{ $type }}" {{ $attributes->class(\App\Support\Ui\ComponentStyles::button($variant, $size)) }}>{{ $slot }}</button>
