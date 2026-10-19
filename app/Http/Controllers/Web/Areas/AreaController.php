<?php

namespace App\Http\Controllers\Web\Areas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Models\Area;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Area::class);

        $areas = Area::withCount(['employees', 'projects'])->when(auth()->user()->isLeader(), fn ($q) => $q->whereHas('employees', fn ($e) => $e->whereIn('id', \App\Support\WorkspaceScope::employees(auth()->user())->select('id'))))->orderBy('nombre')->paginate(\App\Support\ListPagination::size())->withQueryString();

        return view('areas.index', ['areas' => $areas]);
    }

    public function store(StoreAreaRequest $request): RedirectResponse
    {
        $area = Area::create($request->validated() + ['activa' => $request->boolean('active', true)]);

        ActivityLogger::record($area, 'created', "Creó el área \"{$area->nombre}\".");

        return redirect()
            ->route('areas.index')
            ->with('status', 'Área creada correctamente.');
    }

    public function update(UpdateAreaRequest $request, Area $area): RedirectResponse
    {
        $before = $area->getAttributes();

        $area->update($request->validated() + ['activa' => $request->boolean('active')]);

        ActivityLogger::recordUpdate($area, $before, "el área \"{$area->nombre}\"");

        return redirect()
            ->route('areas.index')
            ->with('status', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        $this->authorize('delete', $area);

        if ($area->employees()->exists() || $area->projects()->exists()) {
            return redirect()
                ->route('areas.index')
                ->with('toast_error', 'No se puede eliminar: el área tiene empleados o proyectos asociados.');
        }

        ActivityLogger::record($area, 'deleted', "Eliminó el área \"{$area->nombre}\".");

        $area->delete();

        return redirect()
            ->route('areas.index')
            ->with('status', 'Área eliminada correctamente.');
    }
}
