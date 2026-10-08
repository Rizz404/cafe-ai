@php($isEdit = $menuItem->exists)

<x-layouts.admin :title="$isEdit ? 'Edit menu item' : 'Add menu item'">
    <form method="POST" action="{{ $isEdit ? route('admin.menu-items.update', $menuItem) : route('admin.menu-items.store') }}" class="space-y-8">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.card heading="Basics">
            <div class="mt-4 grid grid-cols-2 gap-4">
                <x-ui.select name="category" label="Category">
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', $menuItem->category) === $category)>{{ \Illuminate\Support\Str::headline($category) }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="price" type="number" label="Price" step="500" :value="old('price', $menuItem->price)" required />
                <x-ui.input name="name" label="Name (Indonesian, default)" :value="old('name', $menuItem->name)" required class="col-span-2" />
                <x-ui.textarea name="description" label="Description (Indonesian, default)" :value="old('description', $menuItem->description)" class="col-span-2" />
                <x-ui.input name="translations[en][name]" label="Name (English)" :value="old('translations.en.name', $menuItem->translations['en']['name'] ?? '')" />
                <x-ui.input name="translations[ja][name]" label="Name (Japanese)" :value="old('translations.ja.name', $menuItem->translations['ja']['name'] ?? '')" />
                <x-ui.textarea name="translations[en][description]" label="Description (English)" :value="old('translations.en.description', $menuItem->translations['en']['description'] ?? '')" />
                <x-ui.textarea name="translations[ja][description]" label="Description (Japanese)" :value="old('translations.ja.description', $menuItem->translations['ja']['description'] ?? '')" />
            </div>
        </x-ui.card>

        <x-ui.card heading="Details">
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-ui.input name="serving" label="Serving" :value="old('serving', $menuItem->serving)" placeholder="hot, iced, hot_iced, shareable" />
                <x-ui.input name="calories" type="number" label="Calories (kcal)" min="0" :value="old('calories', $menuItem->calories)" />
                <x-ui.input name="sort_order" type="number" label="Sort order" :value="old('sort_order', $menuItem->sort_order)" />
            </div>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input name="tags" label="Tags (comma-separated)" :value="old('tags', implode(', ', $menuItem->tags ?? []))" placeholder="halal, vegetarian, signature, spicy" />
                <x-ui.input name="allergens" label="Allergens (comma-separated)" :value="old('allergens', implode(', ', $menuItem->allergens ?? []))" placeholder="milk, gluten, egg, nuts, soy" />
                <x-ui.input name="image_url" label="Image path or URL" :value="old('image_url', $menuItem->image_url)" placeholder="images/menu/cafe-latte.svg or https://…" class="sm:col-span-2" />
            </div>
            <div class="mt-4 flex flex-wrap gap-6">
                <x-ui.checkbox name="is_featured" label="Featured" :checked="old('is_featured', $menuItem->is_featured)" />
                <x-ui.checkbox name="is_sold_out" label="Sold out today" :checked="old('is_sold_out', $menuItem->is_sold_out)" />
                <x-ui.checkbox name="is_active" label="Published (visible on the website)" :checked="old('is_active', $menuItem->is_active)" />
            </div>
        </x-ui.card>

        <div class="flex justify-end gap-3">
            <x-ui.link-button :href="route('admin.menu-items.index')" variant="secondary">Cancel</x-ui.link-button>
            <x-ui.button type="submit">Save menu item</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
