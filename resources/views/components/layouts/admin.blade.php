{{--
    The frame of every admin page. The menu comes from the server (navItems, set
    by a view composer); the cafe name comes from the signed-in account.
    Slots: default, actions (buttons beside the heading). Shows session('status')
    as a success alert.
--}}
@props([
    'title' => null,
    'navItems' => [],
])

@php($cafe = auth()->user()?->currentCafe())

<x-layouts.base :title="$title" :cafe-name="$cafe?->name">
    <div x-data="adminShell" class="min-h-dvh lg:pl-56">
        {{-- Desktop sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-56 flex-col border-r border-border bg-surface lg:flex">
            <x-navigation.brand :name="$cafe?->name" class="border-b border-border px-4 py-4" />
            <div class="flex-1 overflow-y-auto px-2 py-4">
                <x-navigation.admin-sidebar :items="$navItems" />
            </div>
            <form method="POST" action="{{ route('admin.logout') }}" class="border-t border-border p-2">
                @csrf
                <button type="submit" class="w-full rounded-control px-3 py-2 text-left text-sm text-muted hover:bg-surface-hover">Log out</button>
            </form>
        </aside>

        {{-- Mobile drawer (< lg) --}}
        <div x-show="drawerOpen" x-cloak x-on:keydown.escape.window="closeDrawer()" class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-overlay" x-on:click="closeDrawer()" aria-hidden="true"></div>
            <div id="admin-drawer" role="dialog" aria-modal="true" aria-label="Admin menu" class="fixed inset-y-0 left-0 flex w-64 max-w-[85vw] flex-col bg-surface shadow-xl">
                <div class="flex items-center justify-between border-b border-border px-4 py-4">
                    <x-navigation.brand :name="$cafe?->name" />
                    <button type="button" x-on:click="closeDrawer()" class="-m-1 rounded-control p-1 text-muted hover:text-text">
                        <x-ui.icon name="x-mark" label="Close menu" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-2 py-4">
                    <x-navigation.admin-sidebar :items="$navItems" />
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" class="border-t border-border p-2">
                    @csrf
                    <button type="submit" class="w-full rounded-control px-3 py-2 text-left text-sm text-muted hover:bg-surface-hover">Log out</button>
                </form>
            </div>
        </div>

        <div class="flex min-h-dvh flex-col">
            <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-border bg-surface/80 px-4 backdrop-blur-md lg:hidden">
                <button
                    type="button"
                    x-on:click="openDrawer()"
                    aria-controls="admin-drawer"
                    x-bind:aria-expanded="drawerOpen"
                    class="-ml-1 rounded-control p-2 text-text hover:bg-surface-muted"
                >
                    <x-ui.icon name="bars-3" label="Open menu" />
                </button>
                <p class="truncate font-display text-base font-semibold text-primary">{{ $cafe?->name }}</p>
            </header>

            <main id="main-content" tabindex="-1" class="flex-1 focus:outline-none">
                <div class="mx-auto max-w-5xl px-4 py-8 md:px-6">
                    @if (session('status'))
                        <x-ui.alert class="mb-6">{{ session('status') }}</x-ui.alert>
                    @endif

                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <h1 class="font-display text-2xl font-semibold text-primary">{{ $title ?? 'Dashboard' }}</h1>
                        @isset($actions)
                            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
                        @endisset
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</x-layouts.base>
