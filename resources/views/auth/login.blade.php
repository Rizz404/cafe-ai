<x-layouts.guest title="Admin Login">
    <h1 class="font-display text-2xl font-semibold text-primary">Cafe Admin</h1>
    <p class="mt-1 text-sm text-muted">Sign in to manage your cafe's AI Barista.</p>

    @if ($errors->any())
        <x-ui.alert tone="danger" class="mt-4">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4">
        @csrf
        <x-ui.input name="email" type="email" label="Email" :value="old('email')" required autofocus />
        <x-ui.input name="password" type="password" label="Password" required />
        <x-ui.checkbox name="remember" label="Remember me" />
        <x-ui.button type="submit" class="w-full">Sign in</x-ui.button>
    </form>
</x-layouts.guest>
