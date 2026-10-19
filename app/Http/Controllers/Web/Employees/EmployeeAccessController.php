<?php

namespace App\Http\Controllers\Web\Employees;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class EmployeeAccessController extends Controller
{
    private function authorizeEmployee(Employee $employee): void
    {
        $this->authorize('update', $employee);
        abort_unless(auth()->user()->isAdmin() || ! $employee->user || $employee->user->hasExactRoles(['employee']), 403);
    }

    public function edit(Employee $employee)
    {
        $this->authorizeEmployee($employee);

        return view('employees.access', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeEmployee($employee);
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::defaults()]]);
        DB::transaction(function () use ($employee, $data) {
            $employee = Employee::lockForUpdate()->findOrFail($employee->id);
            $this->authorizeEmployee($employee);
            $user = $employee->usuario_id ? User::lockForUpdate()->findOrFail($employee->usuario_id) : null;
            abort_unless(auth()->user()->isAdmin() || !$user || $user->hasExactRoles(['employee']), 403);
            if (! $user) {
                abort_if(User::where('email', $employee->correo)->exists(), 422, 'El correo ya tiene una cuenta. Contacta al administrador para vincularla.');
                $user = User::create(['name' => $employee->fullName(), 'email' => $employee->correo, 'password' => $data['password'], 'force_password_change' => false]);
                $user->assignRole('employee');
                $employee->update(['usuario_id' => $user->id]);
            } else {
                $user->update(['password' => $data['password'], 'force_password_change' => false, 'auth_version' => $user->auth_version + 1, 'remember_token' => Str::random(60)]);
            }
            ActivityLogger::record($employee, 'password_assigned', 'Asignó una contraseña permanente al trabajador.');
        });

        return back()->with('status', 'Contraseña permanente guardada. Las sesiones anteriores quedan invalidadas.');
    }
}
