<?php

namespace App\Http\Controllers\Web\Users;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Enums\RoleName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        return view('users.index', [
            'users' => User::with(['roles', 'employee'])->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))->orderBy('name')->paginate(\App\Support\ListPagination::size())->withQueryString(),
            'employees' => Employee::whereNull('usuario_id')->orderBy('nombres')->get(),
            'roles' => RoleName::cases(),
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::enum(RoleName::class)],
            'employee_id' => ['nullable', 'integer', Rule::exists('empleados', 'id')->whereNull('usuario_id')->whereNull('deleted_at')],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);
        $user = DB::transaction(function () use ($data) {
            $employee = empty($data['employee_id']) ? null : Employee::lockForUpdate()->findOrFail($data['employee_id']);
            if ($employee?->usuario_id) {
                throw ValidationException::withMessages(['employee_id' => 'El empleado ya tiene una cuenta.']);
            }
            $user = User::create(['name' => $data['name'], 'email' => Str::lower($data['email']), 'password' => $data['password'], 'is_active' => true, 'force_password_change' => false]);
            $user->assignRole($data['role']);
            $employee?->update(['usuario_id' => $user->id]);
            ActivityLogger::record($user, 'account_created', 'Creó la cuenta y asignó el rol '.$data['role'].'.');

            return $user;
        });

        return redirect()->route('users.index')->with('status', 'Cuenta creada con contraseña permanente. Entrégala por un canal privado.');
    }

    public function update(Request $request, User $user)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::enum(RoleName::class)],
            'is_active' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($data, $user) {
            $admins = User::role(RoleName::Administrator->value)->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $user->refresh();
            if ($user->is_active && $user->isAdmin() && (! $data['is_active'] || $data['role'] !== RoleName::Administrator->value) && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Debe quedar al menos un administrador activo.']);
            }
            $changedAccess = $user->is_active !== (bool) $data['is_active'] || ! $user->hasExactRoles([$data['role']]) || $user->email !== Str::lower($data['email']);
            $user->update(['name' => $data['name'], 'email' => Str::lower($data['email']), 'is_active' => $data['is_active'], 'auth_version' => $user->auth_version + (int) $changedAccess, 'remember_token' => $changedAccess ? Str::random(60) : $user->remember_token]);
            $user->syncRoles([$data['role']]);
            ActivityLogger::record($user, 'account_updated', 'Actualizó cuenta: rol '.$data['role'].', acceso '.($data['is_active'] ? 'activo' : 'inactivo').'.');
        });

        return back()->with('status', 'Cuenta actualizada. Los cambios de acceso cierran las sesiones anteriores.');
    }

    public function password(Request $request, User $user)
    {
        $data = $request->validate(['admin_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        $user->update(['password' => Hash::make($data['password']), 'force_password_change' => false, 'auth_version' => $user->auth_version + 1, 'remember_token' => Str::random(60)]);
        ActivityLogger::record($user, 'password_reset', 'Estableció una contraseña permanente y cerró las sesiones anteriores.');

        return back()->with('status', 'Contraseña permanente establecida. Las sesiones anteriores quedan invalidadas.');
    }
}
