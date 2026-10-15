<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Políticas de seguridad y cookies') }} · {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased text-slate-900 bg-slate-50 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-10">
        <header class="space-y-2">
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                {{ auth()->check() ? __('Volver al panel') : __('Volver al inicio de sesión') }}
            </a>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">{{ __('Políticas de seguridad y cookies') }}</h1>
            <p class="text-sm text-slate-500">{{ config('app.name') }} — {{ __('Uso interno de la empresa') }}</p>
        </header>

        <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 space-y-4">
            <h2 class="text-lg font-bold text-slate-900">{{ __('1. Política de uso seguro del sistema') }}</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                {{ __('Se proponen las siguientes reglas para el uso del sistema por parte del personal de la empresa:') }}
            </p>
            <ol class="list-decimal list-outside pl-5 space-y-3 text-sm text-slate-700 leading-relaxed">
                <li>{{ __('Cada usuario es responsable de su cuenta. Está prohibido compartir credenciales.') }}</li>
                <li>{{ __('Usar contraseñas robustas y cambiarlas si se sospecha que alguien más las conoce.') }}</li>
                <li>{{ __('Asignar a cada colaborador solo el rol que necesita para su trabajo.') }}</li>
                <li>{{ __('Desactivar el acceso de un colaborador el mismo día en que termina su participación.') }}</li>
                <li>{{ __('Subir a la base de conocimiento solo documentos autorizados y evitar incluir datos personales innecesarios.') }}</li>
                <li>{{ __('Revisar periódicamente la bitácora de actividad y las alertas del sistema.') }}</li>
                <li>{{ __('Realizar copias de seguridad periódicas de la base de datos y de los documentos, y comprobar que se pueden restaurar.') }}</li>
                <li>{{ __('Ante un incidente de seguridad, informar de inmediato al Gerente General. Si involucra datos personales, notificar a la Autoridad Nacional de Protección de Datos Personales dentro de las 48 horas, según el D. S. N.° 016-2024-JUS.') }}</li>
            </ol>
        </section>

        <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 space-y-4">
            <h2 class="text-lg font-bold text-slate-900">{{ __('2. Políticas de la empresa') }}</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                {{ __('El convenio suscrito con Lumen Stratyx E.I.R.L. establece las siguientes obligaciones de seguridad, que se respetaron durante el desarrollo del proyecto:') }}
            </p>
            <ul class="list-disc list-outside pl-5 space-y-3 text-sm text-slate-700 leading-relaxed">
                <li>{{ __('Mantener la confidencialidad de la información de clientes, estrategias, procesos internos, bases de datos, documentación técnica y accesos, incluso después de terminado el convenio.') }}</li>
                <li>{{ __('Usar el correo, hosting, dominios, repositorios y herramientas de la empresa solo con fines profesionales.') }}</li>
                <li>{{ __('Proteger contraseñas, accesos, bases de datos y sistemas, sin compartir credenciales con terceros.') }}</li>
            </ul>
        </section>

        <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 space-y-4">
            <h2 class="text-lg font-bold text-slate-900">{{ __('3. Política de cookies') }}</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                {{ __('Este sistema utiliza únicamente cookies técnicas y esenciales para su funcionamiento. No se usan cookies de publicidad, rastreo entre sitios ni analítica de terceros.') }}
            </p>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead>
                        <tr class="text-xs font-semibold text-slate-500 uppercase tracking-wide border-b border-slate-200">
                            <th class="py-2 pr-4">{{ __('Cookie') }}</th>
                            <th class="py-2 pr-4">{{ __('Finalidad') }}</th>
                            <th class="py-2 pr-4">{{ __('Duración') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr>
                            <td class="py-2.5 pr-4 font-mono text-xs">{{ Str::slug(config('app.name')) }}-session</td>
                            <td class="py-2.5 pr-4">{{ __('Mantiene la sesión del usuario autenticado. Es de solo lectura para el servidor (HttpOnly) y no puede ser leída por scripts del navegador.') }}</td>
                            <td class="py-2.5 pr-4">{{ __('Hasta 120 minutos de inactividad, o al cerrar sesión.') }}</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 pr-4 font-mono text-xs">XSRF-TOKEN</td>
                            <td class="py-2.5 pr-4">{{ __('Protege los formularios y las peticiones del sistema contra ataques de falsificación de solicitudes (CSRF).') }}</td>
                            <td class="py-2.5 pr-4">{{ __('Misma duración que la sesión.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-slate-600 leading-relaxed">
                {{ __('Por ser cookies estrictamente necesarias para el funcionamiento del sistema, no se requiere un banner de consentimiento. Puedes eliminarlas en cualquier momento desde la configuración de tu navegador; esto cerrará tu sesión activa.') }}
            </p>
            <p class="text-sm text-slate-600 leading-relaxed">
                {{ __('El sistema no almacena en tu navegador (localStorage) ningún dato personal, credencial o token de acceso: solo una preferencia de interfaz no sensible (si el menú lateral está colapsado o expandido).') }}
            </p>
        </section>

        <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 space-y-3">
            <h2 class="text-lg font-bold text-slate-900">{{ __('Contacto') }}</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                {{ __('Ante cualquier duda sobre estas políticas, o para reportar un incidente de seguridad, comunícate de inmediato con el Gerente General de la empresa.') }}
            </p>
        </section>
    </div>
</body>
</html>
