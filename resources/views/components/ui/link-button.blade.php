@props([
    'href',
    'variant' => 'primary',
    'size' => 'md',
])

<a href="{{ $href }}" {{ $attributes->class(\App\Support\Ui\ComponentStyles::button($variant, $size)) }}>{{ $slot }}</a>
