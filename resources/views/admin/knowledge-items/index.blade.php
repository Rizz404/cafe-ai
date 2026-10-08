<x-layouts.admin title="Knowledge base">
    <x-slot:actions>
        <x-ui.link-button :href="route('admin.knowledge-items.create')" size="sm">Add entry</x-ui.link-button>
    </x-slot:actions>

    <p class="mb-4 text-sm text-muted">This is the only source the AI Barista is allowed to answer cafe-fact questions from. If it's not here, the AI won't guess.</p>

    <x-ui.table :columns="['Category', 'Title', 'Status', '']" :empty="$items->isEmpty()" empty-text="No knowledge base entries yet.">
        @foreach ($items as $item)
            <tr>
                <td class="px-4 py-3 text-muted">{{ ucfirst($item->category) }}</td>
                <td class="px-4 py-3 font-medium text-text">{{ $item->title }}</td>
                <td class="px-4 py-3">
                    <x-ui.badge :tone="$item->is_active ? 'success' : 'neutral'">{{ $item->is_active ? 'Active' : 'Hidden' }}</x-ui.badge>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.knowledge-items.edit', $item) }}" class="text-muted hover:underline">Edit</a>
                    <x-ui.confirm-form :action="route('admin.knowledge-items.destroy', $item)" message="Delete this entry?" confirm-label="Delete" trigger-class="ml-3 text-danger hover:underline">Delete</x-ui.confirm-form>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
</x-layouts.admin>
