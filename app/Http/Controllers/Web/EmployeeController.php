<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Area;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeMetricsService;
use App\Support\ActivityLogger;
use App\Support\Enums\EmployeeSpecialty;
use App\Support\Enums\EmployeeStatus;
use App\Support\Enums\RoleName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        unset($data['create_access'], $data['password']);

        $employee = DB::transaction(function () use ($data, $createAccess, $password) {
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
        unset($data['create_access'], $data['password']);

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
