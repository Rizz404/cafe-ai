@props([
    'tone' => 'success',
])

<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class(\App\Support\Ui\ComponentStyles::alert($tone)) }}>{{ $slot }}</div>
