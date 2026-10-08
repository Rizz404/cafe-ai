@props([
    'tone' => 'neutral',
])

<span {{ $attributes->class(\App\Support\Ui\ComponentStyles::badge($tone)) }}>{{ $slot }}</span>
