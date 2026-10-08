{{-- The document shell for every non-stage page: one Vite bundle, CSRF meta, skip link. --}}
@props([
    'title' => null,
    'cafeName' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.($cafeName ?? config('app.name')) : ($cafeName ?? config('app.name')) }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body {{ $attributes->class('min-h-dvh bg-canvas font-sans text-text antialiased') }}>
    <a href="#main-content" class="skip-link">Skip to content</a>

    {{ $slot }}
</body>
</html>
