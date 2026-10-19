<?php

namespace App\Http\Controllers\Web\Normativa;

use App\Http\Controllers\Controller;
use App\Models\Falta;
use App\Models\TerminosReferencia;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Un TDR por área (software, web, marketing) y un manual de faltas y sanciones
 * (generales o por área). Lo ve cualquier usuario autenticado; solo el
 * administrador lo edita (ver middleware en routes/web.php).
 */
class NormativaController extends Controller
{
    public function index(Request $request): View
    {
        $areas = \App\Support\WorkspaceAccess::areasFor($request->user());
        abort_if($areas === [], 403);
        abort_if($request->filled('area') && !array_key_exists($request->query('area'), $areas), 403);
        $area = $request->query('area');

        if (! array_key_exists($area, $areas)) {
            // Por defecto, el área del propio colaborador si la tiene.
            $area = $request->user()->employee?->especialidad?->tdrArea() ?? array_key_first($areas);
        }

        $tdr = TerminosReferencia::porArea($area) ?? abort(404);

        return view('normativa.index', [
            'areas' => $areas,
            'area' => $area,
            'tdr' => $tdr,
            'faltas' => \App\Support\ListPagination::collection($tdr->faltasAplicables()),
            'todasLasFaltas' => Falta::where(fn ($q) => $q->whereNull('area')->orWhereIn('area', array_keys($areas)))->orderBy('codigo')->get(),
            'gravedades' => Falta::GRAVEDADES,
        ]);
    }

    public function updateTdr(Request $request, TerminosReferencia $tdr): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'contenido' => ['required', 'string', 'max:40000'],
            'entregables' => ['nullable', 'array', 'max:24'],
            'entregables.*.producto' => ['required', 'string', 'max:500'],
            'entregables.*.plazo' => ['required', 'string', 'max:500'],
        ]);

        $tdr->update([
            'nombre' => $data['nombre'],
            'contenido' => $data['contenido'],
            'entregables' => array_values($data['entregables'] ?? []),
        ]);
        $tdr->nuevaVersion();

        ActivityLogger::record($tdr, 'updated', "Actualizó el TDR de {$tdr->areaLabel()}.");

        return redirect()->route('normativa.index', ['area' => $tdr->area])
            ->with('status', 'TDR actualizado. Los colaboradores del área deberán volver a aceptar la nueva versión.');
    }

    public function storeFalta(Request $request): RedirectResponse
    {
        $falta = Falta::create($this->validated($request) + ['activa' => $request->boolean('activa', true)]);
        TerminosReferencia::nuevaVersionPara($falta->area);

        ActivityLogger::record($falta, 'created', "Registró la falta {$falta->codigo}.");

        return $this->volver($request, 'Falta registrada.');
    }

    public function updateFalta(Request $request, Falta $falta): RedirectResponse
    {
        $this->authorizeArea($request, $falta->area);
        $areaAnterior = $falta->area;
        $falta->update($this->validated($request, $falta) + ['activa' => $request->boolean('activa')]);

        // Cambiar el área de una falta afecta al TDR anterior y al nuevo.
        TerminosReferencia::nuevaVersionPara($falta->area);
        if ($areaAnterior !== $falta->area) {
            TerminosReferencia::nuevaVersionPara($areaAnterior);
        }

        ActivityLogger::record($falta, 'updated', "Actualizó la falta {$falta->codigo}.");

        return $this->volver($request, 'Falta actualizada.');
    }

    public function destroyFalta(Request $request, Falta $falta): RedirectResponse
    {
        $this->authorizeArea($request, $falta->area);
        ActivityLogger::record($falta, 'deleted', "Eliminó la falta {$falta->codigo}.");

        $area = $falta->area;
        $falta->delete();
        TerminosReferencia::nuevaVersionPara($area);

        return $this->volver($request, 'Falta eliminada.');
    }

    private function authorizeArea(Request $request, ?string $area): void
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isManager() || ($request->user()->isLeader() && $area !== null && array_key_exists($area, \App\Support\WorkspaceAccess::areasFor($request->user()))), 403);
    }

    private function volver(Request $request, string $mensaje): RedirectResponse
    {
        return redirect()->route('normativa.index', ['area' => $request->input('_area')])->with('status', $mensaje);
    }

    private function validated(Request $request, ?Falta $falta = null): array
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:20', Rule::unique('faltas', 'codigo')->ignore($falta?->id)],
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['required', 'string', 'max:3000'],
            'gravedad' => ['required', Rule::in(array_keys(Falta::GRAVEDADES))],
            'area' => ['nullable', Rule::in(array_keys(TerminosReferencia::AREAS))],
            'sancion' => ['nullable', 'string', 'max:1000'],
            'monto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $data['area'] = $data['area'] ?? null;

        $this->authorizeArea($request, $data['area']);
        return $data;
    }
}
