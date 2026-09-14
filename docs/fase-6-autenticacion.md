# FASE 6 — Autenticación

## 1. Qué se construyó

Login, registro, cierre de sesión, recuperación/cambio de contraseña, confirmación de contraseña y edición de perfil, usando **Laravel Breeze** (stack Blade) como base, reorganizado luego a nuestra convención de carpetas (`html/js/css` por módulo, componentes en `components/ui|forms|layouts`).

## 2. Por qué Breeze y no autenticación manual

Breeze es el starter kit oficial de Laravel para este caso exacto (Blade + Tailwind, sin SPA), genera controladores, rutas, requests y vistas ya probadas por el equipo de Laravel, y evita reinventar manejo de tokens de reset, hashing, throttling de intentos de login, etc. Confirmé antes de instalar que **Breeze v2.4.x soporta Laravel 13** (`illuminate/console: ^11.0|^12.0|^13.0`) — se instaló la v2.4.2.

## 3. Comandos ejecutados

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
```

Esto instaló dependencias npm (Tailwind, Alpine.js, `@tailwindcss/forms`) y compiló los assets (`npm run build` corrió como parte del comando).

## 4. Reorganización aplicada (Breeze genera todo "plano"; lo movimos a nuestra convención)

| Generado por Breeze | Ubicación final |
|---|---|
| `resources/views/auth/{login,register,forgot-password,reset-password,confirm-password,verify-email}.blade.php` | `resources/views/auth/html/...` |
| `resources/views/dashboard.blade.php` | `resources/views/dashboard/html/index.blade.php` |
| `resources/views/profile/edit.blade.php` + `profile/partials/*` | `resources/views/profile/html/edit.blade.php` + `profile/html/partials/*` |
| `resources/views/layouts/navigation.blade.php` | `resources/views/components/layouts/navbar.blade.php` |
| `resources/views/components/{primary,secondary,danger}-button.blade.php`, `dropdown*.blade.php`, `modal.blade.php`, `nav-link.blade.php`, `responsive-nav-link.blade.php`, `application-logo.blade.php`, `auth-session-status.blade.php` | `resources/views/components/ui/...` |
| `resources/views/components/{input-label,input-error,text-input}.blade.php` | `resources/views/components/forms/...` |
| `resources/views/layouts/{app,guest}.blade.php` | Sin cambios — ya coinciden con la Fase 2 |

No se crearon carpetas `auth/js`, `auth/css`, `dashboard/js`, `dashboard/css`, `profile/js`, `profile/css` porque ninguna vista de esta fase necesita JS/CSS propio todavía (Breeze no genera nada ahí) — se crearán en la fase que efectivamente las necesite, siguiendo el criterio ya fijado en la Fase 2.

## 5. Referencias actualizadas (mover archivos no alcanza; hay que actualizar quién los nombra)

**Tags de componentes** (dentro de las vistas movidas y `layouts/app.blade.php`/`guest.blade.php`), por ejemplo:
- `<x-primary-button>` → `<x-ui.primary-button>`
- `<x-text-input>` → `<x-forms.text-input>`
- `<x-input-label>` → `<x-forms.input-label>`
- `<x-input-error>` → `<x-forms.input-error>`
- `<x-dropdown-link>` → `<x-ui.dropdown-link>`, `<x-application-logo>` → `<x-ui.application-logo>`, etc. (misma lógica para los 13 componentes movidos)

**`resources/views/layouts/app.blade.php`**: `@include('layouts.navigation')` → `<x-layouts.navbar />`.

**Controladores** (`app/Http/Controllers/Auth/*.php`, `ProfileController.php`) y **`routes/web.php`**: cada `view('auth.login')`, `view('dashboard')`, `view('profile.edit', ...)` actualizado a `view('auth.html.login')`, `view('dashboard.html.index')`, `view('profile.html.edit', ...)`.

**`resources/views/profile/html/edit.blade.php`**: los tres `@include('profile.partials....')` actualizados a `@include('profile.html.partials....')`.

## 6. Verificación realizada (no solo "debería funcionar" — se probó de punta a punta)

Levanté el servidor de desarrollo y ejecuté un flujo real vía HTTP (registro → sesión → páginas protegidas → logout → login):

| Prueba | Resultado |
|---|---|
| `GET /login` | 200 |
| `GET /register` | 200 |
| `GET /dashboard` sin sesión | 302 → redirige a `/login` (protección funcionando) |
| `POST /register` con datos válidos | 302 → redirige a `/dashboard` (usuario creado y autenticado) |
| `GET /dashboard` autenticado | 200, contenido "Dashboard" presente |
| `GET /profile` autenticado | **500** en el primer intento — ver sección 7 |
| `GET /profile` autenticado (tras el fix) | 200 |
| `POST /logout` | 302 |
| `GET /login` tras logout | 200 |
| `POST /login` con el usuario creado | 302 → `/dashboard` |
| `GET /forgot-password` | 200 |

El usuario de prueba (`prueba.fase6@example.com`) se eliminó de la base de datos al terminar; no queda como dato de prueba residual.

## 7. Error encontrado y corregido durante la verificación

`GET /profile` devolvía `500 — View [profile.partials.update-profile-information-form] not found`. Causa: al mover los partials a `profile/html/partials/`, los tres `@include('profile.partials....')` dentro de `profile/html/edit.blade.php` seguían apuntando a la ruta vieja (`profile.partials....` en vez de `profile.html.partials....`). Se corrigió y se volvió a verificar — 200 OK. Este es exactamente el tipo de error que se previno en la Fase 3 §15 ("no generes migraciones/código sin probarlo"): mover archivos sin actualizar todas sus referencias es el error más común de este tipo de reorganización, y por eso se hizo la prueba end-to-end en vez de asumir que "compilar sin errores" era suficiente.

## 8. Hallazgo de Laravel 13 relevante para la arquitectura

`<x-app-layout>` y `<x-guest-layout>` resuelven directamente contra `resources/views/layouts/app.blade.php` y `guest.blade.php` sin necesidad de un archivo en `components/`. Se confirmó empíricamente (antes de mover nada) que esto es un comportamiento nativo de Laravel 13, no una configuración de Breeze — por eso `layouts/` se dejó exactamente donde Breeze lo generó, coincidiendo con lo ya definido en la Fase 2.

## 9. Cómo probarlo tú mismo

```bash
php artisan serve
```
Abrir `http://127.0.0.1:8000/register`, crear una cuenta, verificar que redirige al dashboard, entrar a "Perfil" desde el menú superior, cerrar sesión y volver a iniciar sesión.

## 10. Pendiente / fuera de alcance de esta fase

- El **diseño visual** (sidebar, navbar tipo "Enterprise SaaS") se deja para la Fase 40 — por ahora se usa el diseño por defecto de Breeze, solo reorganizado de carpeta, no rediseñado.
- **Roles y permisos** (qué puede hacer cada rol) es la Fase 7, todavía no aplican restricciones más allá de "autenticado / no autenticado".
- Los correos de verificación/reset usan las plantillas de correo por defecto de Laravel (no personalizadas) — se revisará si hace falta personalizarlas cuando se trabaje notificaciones/alertas (Fase 18).

---

**Fase 6 completada y verificada de punta a punta (incluyendo un error real encontrado y corregido). Quedo a la espera de tu "CONTINUAR" para pasar a la Fase 7 (Roles y permisos).**
