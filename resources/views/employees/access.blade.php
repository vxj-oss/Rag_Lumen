<x-app-layout><x-slot name="header"><x-ui.page-header title="Acceso del trabajador" :subtitle="$employee->fullName()" /></x-slot>
<div class="mx-auto max-w-xl p-4"><x-ui.card><p class="mb-4 text-sm text-slate-600">La contraseña será permanente. El trabajador no podrá cambiarla desde su cuenta.</p>
@if($errors->any())<div role="alert" class="mb-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('employees.access.update', $employee) }}" class="space-y-4">@csrf @method('PUT')
<label class="block text-sm">Tu contraseña actual<input type="password" name="current_password" autocomplete="current-password" required class="mt-1 w-full rounded-xl border-slate-300"></label>
<label class="block text-sm">Contraseña permanente del trabajador<input type="password" name="password" autocomplete="new-password" minlength="12" required class="mt-1 w-full rounded-xl border-slate-300"></label>
<label class="block text-sm">Confirmar contraseña<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required class="mt-1 w-full rounded-xl border-slate-300"></label>
<x-ui.primary-button>Guardar contraseña</x-ui.primary-button></form></x-ui.card></div></x-app-layout>
