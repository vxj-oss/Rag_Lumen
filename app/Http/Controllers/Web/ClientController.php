<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::query()
            ->withCount('projects')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('identificacion_fiscal', 'like', "%{$search}%")
                        ->orWhere('nombre_contacto', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('estado', $request->string('status')))
            ->when($request->filled('sector'), fn ($query) => $query->where('sector', $request->string('sector')))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('clients.html.index', [
            'clients' => $clients,
            'sectors' => Client::whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector'),
            'filters' => $request->only(['search', 'status', 'sector']),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        ActivityLogger::record($client, 'created', "Creó el cliente \"{$client->nombre}\".");

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente creado correctamente.');
    }

    public function quickStore(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        ActivityLogger::record($client, 'created', "Creó el cliente \"{$client->nombre}\" (rápido).");

        return response()->json(['id' => $client->id, 'name' => $client->nombre], 201);
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        $client->load(['projects' => fn ($query) => $query->orderByDesc('created_at')]);

        return view('clients.html.show', [
            'client' => $client,
            'totalBudget' => (float) $client->projects->sum('presupuesto'),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $before = $client->getAttributes();

        $client->update($request->validated());

        ActivityLogger::recordUpdate($client, $before, "el cliente \"{$client->nombre}\"");

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        if ($client->projects()->exists()) {
            return redirect()
                ->route('clients.index')
                ->with('toast_error', 'No se puede eliminar: el cliente tiene proyectos asociados.');
        }

        ActivityLogger::record($client, 'deleted', "Eliminó el cliente \"{$client->nombre}\".");

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente eliminado correctamente.');
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::withCount('projects')->orderBy('nombre')->get();

        return response()->streamDownload(function () use ($clients) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Razón social', 'RUC/ID', 'Contacto', 'Email', 'Teléfono', 'Sector', 'Estado', 'Proyectos']);

            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->nombre,
                    $client->identificacion_fiscal ?? '—',
                    $client->nombre_contacto ?? '—',
                    $client->correo ?? '—',
                    $client->telefono ?? '—',
                    $client->sector ?? '—',
                    $client->estado === 'active' ? 'Activo' : 'Inactivo',
                    $client->projects_count,
                ]);
            }

            fclose($handle);
        }, 'clientes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
