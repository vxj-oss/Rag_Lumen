<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeBreachNoticeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Area;
use App\Models\Employee;
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

        $employees = Employee::query()
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
            ->paginate(5)
            ->withQueryString();

        return view('employees.html.index', [
            'employees' => $employees,
            'specialties' => EmployeeSpecialty::cases(),
            'statuses' => EmployeeStatus::cases(),
            'areas' => Area::where('activa', true)->orderBy('nombre')->get(),
            'totalCount' => Employee::count(),
            'activeCount' => Employee::where('estado', EmployeeStatus::Active->value)->count(),
            'onLeaveCount' => Employee::where('estado', EmployeeStatus::OnLeave->value)->count(),
            'filters' => $request->only(['search', 'status', 'specialty', 'area_id']),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::orderBy('nombres')->get();

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
                $data['firma_path'] = $firma->store('firmas/colaboradores', 'local');
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
        ]);

        $user->assignRole(RoleName::Employee->value);

        $employee->update(['usuario_id' => $user->id]);
    }

    public function show(Employee $employee, EmployeeMetricsService $metricsService): View
    {
        $this->authorize('view', $employee);

        return view('employees.html.show', [
            'employee' => $employee,
            'specialties' => EmployeeSpecialty::cases(),
            'statuses' => EmployeeStatus::cases(),
            'areas' => Area::where('activa', true)->orderBy('nombre')->get(),
            'metrics' => $metricsService->forEmployee($employee),
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

            $data['firma_path'] = $firma->store('firmas/colaboradores', 'local');
        }

        $employee->update($data);

        ActivityLogger::recordUpdate($employee, $before, "al empleado \"{$employee->fullName()}\"");

        if ($employee->usuario_id === null && $createAccess && $password) {
            $this->createUserAccount($employee, $password);
            ActivityLogger::record($employee, 'updated', "Le creó acceso al sistema a \"{$employee->fullName()}\".");
        } elseif ($employee->usuario_id !== null && $password) {
            $employee->user->update(['password' => $password]);
            ActivityLogger::record($employee, 'updated', "Restableció la contraseña de \"{$employee->fullName()}\".");
        }

        return redirect()
            ->route('employees.index')
            ->with('status', 'Empleado actualizado correctamente.');
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
        $this->authorize('update', $employee);

        return Pdf::loadView('reports.employee-contract', [
            'employee' => $employee,
            'generatedAt' => now(),
            'empresaFirma' => CompanySignature::base64(),
        ])
            ->setPaper('a4', 'portrait')
            ->download('convenio-'.Str::slug($employee->fullName()).'.pdf');
    }

    public function breachNotice(StoreEmployeeBreachNoticeRequest $request, Employee $employee): Response
    {
        $data = $request->validated();

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

        ActivityLogger::record($employee, 'deleted', "Eliminó al empleado \"{$employee->fullName()}\".");

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('status', 'Empleado eliminado correctamente.');
    }
}
