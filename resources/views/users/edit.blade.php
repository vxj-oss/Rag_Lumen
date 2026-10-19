<x-app-layout>
    <x-slot name="header"><x-ui.page-header :title="$account->name" :subtitle="__('Administración de cuenta y contraseña')" /></x-slot>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6">
        <a class="workspace-button" href="{{ route('users.index') }}">← {{ __('Volver a usuarios') }}</a>
        @if($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <x-ui.card><h2 class="workspace-section-title mb-4">{{ __('Cuenta y permisos') }}</h2><p class="mb-4 text-sm text-slate-600">{{ __('Empleado vinculado') }}: {{ $account->employee?->fullName() ?? __('Sin vínculo') }}</p>
            <form method="POST" action="{{ route('users.update', $account) }}" data-submit-feedback class="space-y-4">@csrf @method('PUT')
                <label class="block text-sm font-medium">{{ __('Nombre') }}<input name="name" value="{{ old('name',$account->name) }}" required class="mt-1 w-full rounded-xl border-slate-300"></label>
                <label class="block text-sm font-medium">{{ __('Correo de acceso') }}<input type="email" name="email" value="{{ old('email',$account->email) }}" required class="mt-1 w-full rounded-xl border-slate-300"></label>
                <label class="block text-sm font-medium">{{ __('Rol') }}<select name="role" class="mt-1 w-full rounded-xl border-slate-300">@foreach($roles as $role)<option value="{{ $role->value }}" @selected(old('role',$account->roles->first()?->name) === $role->value)>{{ $role->label() }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium">{{ __('Acceso') }}<select name="is_active" class="mt-1 w-full rounded-xl border-slate-300"><option value="1" @selected(old('is_active',$account->is_active) == 1)>{{ __('Activo') }}</option><option value="0" @selected(old('is_active',$account->is_active) == 0)>{{ __('Desactivado') }}</option></select></label>
                <x-ui.primary-button>{{ __('Guardar permisos') }}</x-ui.primary-button>
            </form>
        </x-ui.card>
        <x-ui.card><h2 class="workspace-section-title mb-3">{{ __('Restablecer contraseña') }}</h2><p class="mb-4 text-sm text-slate-600">{{ __('Se cerrarán las sesiones anteriores. La contraseña asignada será la definitiva.') }}</p>
            <form method="POST" action="{{ route('users.password',$account) }}" data-submit-feedback class="space-y-4">@csrf @method('PUT')
                <label class="block text-sm font-medium">{{ __('Tu contraseña de administrador') }}<input type="password" name="admin_password" required autocomplete="current-password" class="mt-1 w-full rounded-xl border-slate-300"></label>
                <div class="grid gap-3 sm:grid-cols-2"><label class="text-sm font-medium">{{ __('Nueva contraseña permanente') }}<input type="password" name="password" required minlength="12" autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-300"></label><label class="text-sm font-medium">{{ __('Confirmación') }}<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-300"></label></div>
                <x-ui.primary-button>{{ __('Restablecer contraseña') }}</x-ui.primary-button>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
