<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCafe;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SeatingAreaRequest;
use App\Models\SeatingArea;
use App\Modules\Seating\Actions\SaveSeatingArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SeatingAreaController extends Controller
{
    use ResolvesCurrentCafe;

    public function index(Request $request): View
    {
        $cafe = $this->currentCafe($request);

        $seatingAreas = $cafe->seatingAreas()->withCount('images')->orderBy('sort_order')->get();

        return view('admin.seating-areas.index', compact('cafe', 'seatingAreas'));
    }

    public function create(Request $request): View
    {
        $cafe = $this->currentCafe($request);

        return view('admin.seating-areas.form', [
            'cafe' => $cafe,
            'seatingArea' => new SeatingArea(['area_type' => SeatingArea::TYPE_INDOOR, 'min_guests' => 1, 'max_guests' => 4, 'reservation_fee' => 0, 'is_active' => true]),
        ]);
    }

    public function store(SeatingAreaRequest $request, SaveSeatingArea $save): RedirectResponse
    {
        $seatingArea = $save->handle(
            $this->currentCafe($request),
            $request->seatingAreaAttributes(),
            $request->imageIdsToDelete(),
            $request->newImages(),
        );

        return redirect()->route('admin.seating-areas.index')->with('status', "Seating area \"{$seatingArea->name}\" created.");
    }

    public function edit(Request $request, SeatingArea $seatingArea): View
    {
        $cafe = $this->currentCafe($request);
        Gate::authorize('update', $seatingArea);

        $seatingArea->load('images');

        return view('admin.seating-areas.form', compact('cafe', 'seatingArea'));
    }

    public function update(SeatingAreaRequest $request, SeatingArea $seatingArea, SaveSeatingArea $save): RedirectResponse
    {
        $save->handle($this->currentCafe($request), $request->seatingAreaAttributes(), $request->imageIdsToDelete(), $request->newImages(), $seatingArea);

        return redirect()->route('admin.seating-areas.index')->with('status', "Seating area \"{$seatingArea->name}\" updated.");
    }

    public function destroy(Request $request, SeatingArea $seatingArea): RedirectResponse
    {
        $this->currentCafe($request);
        Gate::authorize('delete', $seatingArea);

        $seatingArea->delete();

        return redirect()->route('admin.seating-areas.index')->with('status', 'Seating area deleted.');
    }
}
