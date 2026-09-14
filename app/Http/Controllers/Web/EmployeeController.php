<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
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

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('specialty'), fn ($query) => $query->where('specialty', $request->string('specialty')))
            ->orderBy('first_name')
            ->paginate(5)
            ->withQueryString();

        return view('employees.html.index', [
            'employees' => $employees,
            'specialties' => EmployeeSpecialty::cases(),
            'statuses' => EmployeeStatus::cases(),
            'totalCount' => Employee::count(),
            'activeCount' => Employee::where('status', EmployeeStatus::Active->value)->count(),
            'onLeaveCount' => Employee::where('status', EmployeeStatus::OnLeave->value)->count(),
            'filters' => $request->only(['search', 'status', 'specialty']),
        ]);
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::orderBy('first_name')->get();

        return response()->streamDownload(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'Especialidad', 'Estado', 'Email', 'Teléfono', 'Fecha de contratación']);

            foreach ($employees as $employee) {
                fputcsv($handle, [
                    $employee->fullName(),
                    $employee->specialty->label(),
                    $employee->status->label(),
                    $employee->email,
                    $employee->phone ?? '—',
                    $employee->hire_date?->toDateString() ?? '—',
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
            'email' => $employee->email,
            'password' => $password,
        ]);

        $user->assignRole(RoleName::Employee->value);

        $employee->update(['user_id' => $user->id]);
    }

    public function show(Employee $employee, EmployeeMetricsService $metricsService): View
    {
        $this->authorize('view', $employee);

        return view('employees.html.show', [
            'employee' => $employee,
            'specialties' => EmployeeSpecialty::cases(),
            'statuses' => EmployeeStatus::cases(),
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

        if ($employee->user_id === null && $createAccess && $password) {
            $this->createUserAccount($employee, $password);
            ActivityLogger::record($employee, 'updated', "Le creó acceso al sistema a \"{$employee->fullName()}\".");
        } elseif ($employee->user_id !== null && $password) {
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
