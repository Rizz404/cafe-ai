<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCafe;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KnowledgeItemRequest;
use App\Models\CafeKnowledgeItem;
use App\Modules\Knowledge\Enums\KnowledgeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KnowledgeItemController extends Controller
{
    use ResolvesCurrentCafe;

    public function index(Request $request): View
    {
        $cafe = $this->currentCafe($request);

        $items = $cafe->knowledgeItems()->orderBy('category')->orderBy('sort_order')->get();

        return view('admin.knowledge-items.index', compact('cafe', 'items'));
    }

    public function create(Request $request): View
    {
        $cafe = $this->currentCafe($request);

        return view('admin.knowledge-items.form', [
            'cafe' => $cafe,
            'item' => new CafeKnowledgeItem(['category' => CafeKnowledgeItem::CATEGORY_GENERAL, 'is_active' => true]),
            'categories' => KnowledgeCategory::values(),
        ]);
    }

    public function store(KnowledgeItemRequest $request): RedirectResponse
    {
        $item = $this->currentCafe($request)->knowledgeItems()->create($request->knowledgeItemAttributes());

        return redirect()->route('admin.knowledge-items.index')->with('status', "Knowledge item \"{$item->title}\" created.");
    }

    public function edit(Request $request, CafeKnowledgeItem $knowledgeItem): View
    {
        $cafe = $this->currentCafe($request);
        Gate::authorize('update', $knowledgeItem);

        return view('admin.knowledge-items.form', [
            'cafe' => $cafe,
            'item' => $knowledgeItem,
            'categories' => KnowledgeCategory::values(),
        ]);
    }

    public function update(KnowledgeItemRequest $request, CafeKnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->currentCafe($request);

        $knowledgeItem->update($request->knowledgeItemAttributes());

        return redirect()->route('admin.knowledge-items.index')->with('status', "Knowledge item \"{$knowledgeItem->title}\" updated.");
    }

    public function destroy(Request $request, CafeKnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->currentCafe($request);
        Gate::authorize('delete', $knowledgeItem);

        $knowledgeItem->delete();

        return redirect()->route('admin.knowledge-items.index')->with('status', 'Knowledge item deleted.');
    }
}
