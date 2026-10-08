<x-layouts.admin title="Menu">
    <x-slot:actions>
        <x-ui.link-button :href="route('admin.menu-items.create')" size="sm">Add menu item</x-ui.link-button>
    </x-slot:actions>

    <x-ui.table :columns="['Name', 'Category', 'Price', 'Status', '']" :empty="$menuItems->isEmpty()" empty-text="No menu items yet.">
        @foreach ($menuItems as $menuItem)
            <tr>
                <td class="px-4 py-3 font-medium text-text">{{ $menuItem->name }}@if ($menuItem->is_featured)<x-ui.badge tone="warning" class="ml-2">Featured</x-ui.badge>@endif</td>
                <td class="px-4 py-3 text-muted">{{ \Illuminate\Support\Str::headline($menuItem->category) }}</td>
                <td class="px-4 py-3">{{ $cafe->currency }} {{ number_format((float) $menuItem->price, 0, ',', '.') }}</td>
                <td class="px-4 py-3">
                    <x-ui.badge :tone="$menuItem->is_active ? 'success' : 'neutral'">{{ $menuItem->is_active ? 'Active' : 'Hidden' }}</x-ui.badge>
                    @if ($menuItem->is_sold_out)
                        <x-ui.badge tone="danger" class="ml-1">Sold out</x-ui.badge>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.menu-items.edit', $menuItem) }}" class="text-muted hover:underline">Edit</a>
                    <x-ui.confirm-form :action="route('admin.menu-items.destroy', $menuItem)" message="Delete this menu item?" confirm-label="Delete" trigger-class="ml-3 text-danger hover:underline">Delete</x-ui.confirm-form>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
</x-layouts.admin>
