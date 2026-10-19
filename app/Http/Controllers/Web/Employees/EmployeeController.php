<?php

namespace App\Http\Controllers\Web\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeBreachNoticeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Area;
use App\Models\Employee;
use App\Models\Falta;
use App\Models\TerminosReferencia;
use App\Models\User;
use App\Services\EmployeeMetricsService;
use App\Support\ActivityLogger;
use App\Support\CompanySignature;
use App\Support\Enums\EmployeeSpecialty;
use App\Support\Enums\EmployeeStatus;
use App\Support\Enums\RoleName;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = \App\Support\WorkspaceScope::employees($request->user())
            ->with('area')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%")
                        ->orWhere('correo', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('estado', $request->string('status')))
            ->when($request->filled('specialty'), fn ($query) => $query->where('especialidad', $request->string('specialty')))
            ->when($request->filled('area_id'), fn ($query) => $query->where('area_id', $request->integer('area_id')))
            ->orderBy('nombres')
            ->paginate(\App\Support\ListPagination::size())
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'specialties' => EmployeeSpecialty::cases(),
            'statuses' => EmployeeStatus::cases(),
            'areas' => Area::where('activa', true)->orderBy('nombre')->get(),
            'totalCount' => \App\Support\WorkspaceScope::employees($request->user())->count(),
            'activeCount' => \App\Support\WorkspaceScope::employees($request->user())->where('estado', EmployeeStatus::Active->value)->count(),
            'onLeaveCount' => \App\Support\WorkspaceScope::employees($request->user())->where('estado', EmployeeStatus::OnLeave->value)->count(),
            'filters' => $request->only(['search', 'status', 'specialty', 'area_id']),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $employees = \App\Support\WorkspaceScope::employees(auth()->user())->orderBy('nombres')->get();

        return response()->streamDownload(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'Especialidad', 'Estado', 'Email', 'Teléfono', 'Fecha de contratación']);

            foreach ($employees as $employee) {
                fputcsv($handle, [
                    $employee->fullName(),
                    $employee->especialidad->label(),
                    $employee->estado->label(),
                    $employee->correo,
                    $employee->telefono ?? '—',
                    $employee->fecha_contratacion?->toDateString() ?? '—',
                ]);
            }

            fclose($handle);
        }, 'empleados-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $createAccess = $request->boolean('create_access');
        $password = $data['password'] ?? null;
        $firma = $request->file('firma');
        unset($data['create_access'], $data['password'], $data['firma']);

        $employee = DB::transaction(function () use ($data, $createAccess, $password, $firma) {
            if ($firma !== null) {
                $data['firma_path'] = $this->guardarFirma($firma);
            }

            $employee = Employee::create($data);

            if ($createAccess) {
                $this->createUserAccount($employee, $password);
            }

            return $employee;
        });

        $message = "Creó al empleado \"{$employee->fullName()}\".";

        if ($createAccess) {
            $message .= ' Le creó acceso al sistema.';
        }

        ActivityLogger::record($employee, 'created', $message);

        return redirect()
            ->route('employees.index')
            ->with('status', 'Empleado creado correctamente.');
    }

    private function createUserAccount(Employee $employee, string $password): void
    {
        $user = User::create([
            'name' => $employee->fullName(),
            'email' => $employee->correo,
            'password' => $password,
            'force_password_change' => false,
        ]);

        $user->assignRole(RoleName::Employee->value);

        $employee->update(['usuario_id' => $user->id]);
    }

    public function show(Employee $employee, EmployeeMetricsService $metricsService): View
    {
        $this->authorize('view', $employee);

        return view('employees.show', [
            'employee' => $employee,
            'specialties' => EmployeeSpecialty::cases(),
            'statuses' => EmployeeStatus::cases(),
            'areas' => Area::where('activa', true)->orderBy('nombre')->get(),
            'metrics' => $metricsService->forEmployee($employee),
            'sanctions' => \App\Models\EmployeeSanction::where('empleado_id', $employee->id)->latest()->paginate(\App\Support\ListPagination::size())->withQueryString(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $before = $employee->getAttributes();

        $data = $request->validated();
        $createAccess = $request->boolean('create_access');
        $password = $data['password'] ?? null;
        $firma = $request->file('firma');
        unset($data['create_access'], $data['password'], $data['firma']);

        if ($firma !== null) {
            if ($employee->firma_path !== null) {
                Storage::disk('local')->delete($employee->firma_path);
            }

            $data['firma_path'] = $this->guardarFirma($firma);
        }

        $employee->update($data);

        ActivityLogger::recordUpdate($employee, $before, "al empleado \"{$employee->fullName()}\"");

        if ($employee->usuario_id === null && $createAccess && $password) {
            $this->createUserAccount($employee, $password);
            ActivityLogger::record($employee, 'updated', "Le creó acceso al sistema a \"{$employee->fullName()}\".");
        }

        return redirect()
            ->route('employees.index')
            ->with('status', 'Empleado actualizado correctamente.');
    }

    /** Guarda la firma subida como PNG con el fondo quitado y recortada. */
    private function guardarFirma(\Illuminate\Http\UploadedFile $archivo): string
    {
        $ruta = 'firmas/colaboradores/'.Str::uuid().'.png';

        Storage::disk('local')->put($ruta, \App\Support\SignatureCleaner::clean($archivo->get()));

        return $ruta;
    }

    public function firma(Employee $employee): Response
    {
        $this->authorize('view', $employee);

        abort_if($employee->firma_path === null || ! Storage::disk('local')->exists($employee->firma_path), 404);

        return response(Storage::disk('local')->get($employee->firma_path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($employee->firma_path),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function contract(Employee $employee): Response
    {
        $this->authorize('view', $employee);

        return Pdf::loadView('reports.employee-contract', [
            'employee' => $employee,
            'generatedAt' => now(),
            'empresaFirma' => CompanySignature::base64(),
        ])
            ->setPaper('a4', 'portrait')
            ->download('convenio-'.Str::slug($employee->fullName()).'.pdf');
    }

    /** PDF del TDR del área indicada (por defecto, la que corresponde a la especialidad del colaborador). */
    public function normativa(Request $request, Employee $employee): Response
    {
        $this->authorize('view', $employee);

        $tdr = $this->resolverTdr($request, $employee);

        return Pdf::loadView('reports.employee-normativa', [
            'employee' => $employee,
            'tdr' => $tdr,
            'faltas' => $tdr->faltasAplicables(),
            'generatedAt' => now(),
            'empresaFirma' => CompanySignature::base64(),
        ])
            ->setPaper('a4', 'portrait')
            ->download('tdr-'.$tdr->area.'-'.Str::slug($employee->fullName()).'.pdf');
    }

    /** Registra que el colaborador aceptó y firmó la versión vigente de un TDR. */
    public function aceptarNormativa(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->employee?->id === $employee->id, 403);

        if (! $employee->tieneFirma()) {
            return redirect()->back()->with('toast_error', 'Registra primero la firma del colaborador.');
        }

        $tdr = $this->resolverTdr($request, $employee);

        $employee->update([
            'normativa_tdr_id' => $tdr->id,
            'normativa_version_aceptada' => $tdr->version,
            'normativa_aceptada_en' => now(),
        ]);

        ActivityLogger::record($employee, 'updated', "Aceptó y firmó el TDR de {$tdr->areaLabel()} (versión {$tdr->version}).");

        return redirect()->back()->with('status', 'Aceptación registrada.');
    }

    private function resolverTdr(Request $request, Employee $employee): TerminosReferencia
    {
        $area = $request->input('area') ?? $employee->especialidad?->tdrArea();

        abort_unless(is_string($area) && array_key_exists($area, TerminosReferencia::AREAS), 422, 'Indica el área del TDR.');

        abort_unless(array_key_exists($area, \App\Support\WorkspaceAccess::areasFor($request->user())), 403);
        return TerminosReferencia::porArea($area) ?? abort(404);
    }

    public function breachNotice(StoreEmployeeBreachNoticeRequest $request, Employee $employee): Response
    {
        $data = $request->validated();

        // Si se elige una falta del manual, su referencia y monto rellenan lo que no se escribió a mano.
        if (! empty($data['falta_id'])) {
            $falta = Falta::findOrFail($data['falta_id']);
            $data['clausula_infringida'] = $data['clausula_infringida'] ?? $falta->referencia();
            $data['monto'] = $data['monto'] ?? $falta->monto;
        }

        \App\Models\EmployeeSanction::create(['empleado_id' => $employee->id, 'aplicado_por' => $request->user()->id, 'fecha_hechos' => $data['fecha_hechos'], 'hechos' => $data['hechos'], 'clausula_infringida' => $data['clausula_infringida'] ?? null, 'monto' => $data['monto'] ?? null]);

        ActivityLogger::record($employee, 'updated', "Generó un acta de incumplimiento para \"{$employee->fullName()}\".");

        return Pdf::loadView('reports.employee-breach-notice', [
            'employee' => $employee,
            'generatedAt' => now(),
            'fechaHechos' => Carbon::parse($data['fecha_hechos']),
            'hechos' => $data['hechos'],
            'clausulaInfringida' => $data['clausula_infringida'] ?? null,
            'monto' => $data['monto'] ?? null,
            'empresaFirma' => CompanySignature::base64(),
        ])
            ->setPaper('a4', 'portrait')
            ->download('acta-incumplimiento-'.Str::slug($employee->fullName()).'-'.now()->format('Y-m-d').'.pdf');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);
        if ($employee->user?->is_active) return back()->with('toast_error','Primero desactiva su cuenta en Usuarios y accesos. El registro laboral y el acceso se administran por separado.');

        ActivityLogger::record($employee, 'deleted', "Eliminó al empleado \"{$employee->fullName()}\".");

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('status', 'Empleado eliminado correctamente.');
    }
}
