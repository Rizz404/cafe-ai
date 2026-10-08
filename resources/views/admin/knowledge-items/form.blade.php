@php($isEdit = $item->exists)

<x-layouts.admin :title="$isEdit ? 'Edit knowledge item' : 'Add knowledge item'">
    <form method="POST" action="{{ $isEdit ? route('admin.knowledge-items.update', $item) : route('admin.knowledge-items.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.card>
            <div class="grid grid-cols-2 gap-4">
                <x-ui.select name="category" label="Category">
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', $item->category) === $category)>{{ ucfirst($category) }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="sort_order" type="number" label="Sort order" :value="old('sort_order', $item->sort_order)" />
                <x-ui.input name="tags" label="Tags (comma-separated, helps the AI match guest questions)" :value="old('tags', implode(', ', $item->tags ?? []))" placeholder="wifi, password, internet" class="col-span-2" />
                <x-ui.input name="image_url" label="Image path or URL (shown on the facility cards)" :value="old('image_url', $item->image_url)" placeholder="images/scenes/terrace.svg or https://…" class="col-span-2" />
                <x-ui.checkbox name="is_active" label="Active (the AI can use this entry)" :checked="old('is_active', $item->is_active)" class="col-span-2" />
            </div>
        </x-ui.card>

        <x-ui.card heading="Indonesian (default)">
            <div class="mt-4 space-y-4">
                <x-ui.input name="title" label="Title" :value="old('title', $item->title)" required />
                <x-ui.textarea name="body" label="Body" rows="4" :value="old('body', $item->body)" required />
            </div>
        </x-ui.card>

        <x-ui.card heading="English">
            <div class="mt-4 space-y-4">
                <x-ui.input name="translations[en][title]" label="Title" :value="old('translations.en.title', $item->translations['en']['title'] ?? '')" />
                <x-ui.textarea name="translations[en][body]" label="Body" rows="4" :value="old('translations.en.body', $item->translations['en']['body'] ?? '')" />
            </div>
        </x-ui.card>

        <x-ui.card heading="Japanese">
            <div class="mt-4 space-y-4">
                <x-ui.input name="translations[ja][title]" label="Title" :value="old('translations.ja.title', $item->translations['ja']['title'] ?? '')" />
                <x-ui.textarea name="translations[ja][body]" label="Body" rows="4" :value="old('translations.ja.body', $item->translations['ja']['body'] ?? '')" />
            </div>
        </x-ui.card>

        <div class="flex justify-end gap-3">
            <x-ui.link-button :href="route('admin.knowledge-items.index')" variant="secondary">Cancel</x-ui.link-button>
            <x-ui.button type="submit">Save entry</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
