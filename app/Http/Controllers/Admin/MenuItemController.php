<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCafe;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuItemRequest;
use App\Models\MenuItem;
use App\Modules\Menu\Actions\SaveMenuItem;
use App\Modules\Menu\Enums\MenuCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    use ResolvesCurrentCafe;

    public function index(Request $request): View
    {
        $cafe = $this->currentCafe($request);

        $menuItems = $cafe->menuItems()->orderBy('category')->orderBy('sort_order')->get();

        return view('admin.menu-items.index', compact('cafe', 'menuItems'));
    }

    public function create(Request $request): View
    {
        $cafe = $this->currentCafe($request);

        return view('admin.menu-items.form', [
            'cafe' => $cafe,
            'menuItem' => new MenuItem(['category' => MenuItem::CATEGORY_COFFEE, 'is_active' => true]),
            'categories' => MenuCategory::values(),
        ]);
    }

    public function store(MenuItemRequest $request, SaveMenuItem $save): RedirectResponse
    {
        $menuItem = $save->handle($this->currentCafe($request), $request->menuItemAttributes());

        return redirect()->route('admin.menu-items.index')->with('status', "Menu item \"{$menuItem->name}\" created.");
    }

    public function edit(Request $request, MenuItem $menuItem): View
    {
        $cafe = $this->currentCafe($request);
        Gate::authorize('update', $menuItem);

        return view('admin.menu-items.form', [
            'cafe' => $cafe,
            'menuItem' => $menuItem,
            'categories' => MenuCategory::values(),
        ]);
    }

    public function update(MenuItemRequest $request, MenuItem $menuItem, SaveMenuItem $save): RedirectResponse
    {
        $save->handle($this->currentCafe($request), $request->menuItemAttributes(), $menuItem);

        return redirect()->route('admin.menu-items.index')->with('status', "Menu item \"{$menuItem->name}\" updated.");
    }

    public function destroy(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $this->currentCafe($request);
        Gate::authorize('delete', $menuItem);

        $menuItem->delete();

        return redirect()->route('admin.menu-items.index')->with('status', 'Menu item deleted.');
    }
}
