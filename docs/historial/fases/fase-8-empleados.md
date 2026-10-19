# FASE 8 — Empleados

## 1. Qué se construyó

El primer módulo de dominio real del sistema: CRUD completo de Empleados (listar, crear, ver, editar, eliminar), con autorización por rol y datos según el esquema definido en la Fase 3 §5.

## 2. Arquitectura involucrada

- **Capa de datos**: tabla `employees` (migración), modelo `Employee` con soft deletes.
- **Autorización**: `EmployeePolicy` (Administrador gestiona todo, Gerente solo puede ver, Líder de Proyecto/Empleado sin acceso a este módulo), siguiendo exactamente la matriz de la Fase 1 §7.
- **Presentación**: controlador Blade en `app/Http/Controllers/Web/`, vistas en `resources/views/employees/html/`, nuevo componente reutilizable `x-forms.select`.

## 3. Carpetas y archivos nuevos

```
app/Support/Enums/EmployeeSpecialty.php
app/Support/Enums/EmployeeStatus.php
app/Models/Employee.php
app/Policies/EmployeePolicy.php
app/Http/Controllers/Web/EmployeeController.php
app/Http/Requests/Employee/StoreEmployeeRequest.php
app/Http/Requests/Employee/UpdateEmployeeRequest.php
database/migrations/2026_09_11_055708_create_employees_table.php
database/factories/EmployeeFactory.php
resources/views/employees/html/index.blade.php
resources/views/employees/html/create.blade.php
resources/views/employees/html/edit.blade.php
resources/views/employees/html/show.blade.php
resources/views/employees/html/partials/form.blade.php
resources/views/components/forms/select.blade.php
```

## 4. Archivos modificados

- **`app/Http/Controllers/Controller.php`**: se agregó el trait `AuthorizesRequests` — ver hallazgo importante en la sección 6.
- **`app/Models/User.php`**: se agregó la relación `employee(): HasOne` (para resolver "mi perfil de empleado" desde el usuario autenticado, necesario en fases futuras de Tareas/Proyectos).
- **`routes/web.php`**: se agregó `Route::resource('employees', EmployeeController::class)` dentro del grupo `auth`.
- **`resources/views/components/layouts/navbar.blade.php`**: se agregó el enlace "Empleados" (desktop y responsive), visible solo si `@can('viewAny', Employee::class)`.

## 5. Reglas de autorización aplicadas (`EmployeePolicy`)

| Acción | Quién puede |
|---|---|
| Ver listado / ver detalle | `administrator`, `manager` |
| Crear / editar / eliminar | solo `administrator` |

Consistente con la Fase 1 §7: el Gerente "puede ver empleados" pero no administrarlos.

## 6. Hallazgo importante de Laravel 13 (afecta a todos los módulos futuros)

El controlador base que genera Laravel 13 viene **vacío** (`abstract class Controller {}`), sin los traits `AuthorizesRequests`/`ValidatesRequests` que sí traían versiones anteriores. Esto se detectó porque `$this->authorizeResource(...)` fallaba con `Call to undefined method`. Se corrigió agregando `use AuthorizesRequests;` al controlador base — una sola vez, para que todos los controladores futuros (Proyectos, Tareas, etc.) lo hereden automáticamente.

Además, `authorizeResource()` en sí depende internamente de `$this->middleware(...)`, un mecanismo que el nuevo sistema de controladores de Laravel 11+ ya no soporta de la misma forma. En vez de pelear contra eso, se optó por el enfoque más explícito y transparente: **llamar a `$this->authorize(...)` al inicio de cada método** del controlador (`index`, `create`, `show`, `edit`, `destroy`), y dejar que `store`/`update` se autoricen solos a través del método `authorize()` de su propio Form Request. Es más verboso que `authorizeResource()`, pero es explícito y no depende de un mecanismo interno que cambió entre versiones de Laravel.

## 7. Verificación realizada (con datos reales, no solo "compila sin errores")

| Prueba | Resultado |
|---|---|
| `GET /employees` como `administrator` | 200, listado vacío inicialmente |
| `GET /employees/create` | 200 |
| `POST /employees` con datos válidos | 302 → redirige al listado |
| Empleado creado aparece en `GET /employees` | Confirmado ("Ana Torres" visible) |
| `GET /employees/{id}` (show) | 200 |
| `GET /employees/{id}/edit` → `PUT /employees/{id}` cambiando el cargo | 302, y el cambio se reflejó en la base de datos (verificado por SQL directo) |
| `GET /employees` como usuario con rol `employee` | **403** (la Policy bloquea correctamente) |
| `DELETE /employees/{id}` como `administrator` | 302, y en la base de datos el registro quedó con `deleted_at` seteado (**soft delete**, no borrado físico) |
| Enlace "Empleados" en el navbar | Visible para `administrator` (verificado por HTML de `/dashboard`) |

Todos los usuarios y el empleado de prueba creados durante la verificación se eliminaron al finalizar; no quedan datos de prueba residuales en la base de datos.

## 8. Cómo probarlo tú mismo

Con el servidor corriendo (`php artisan serve`), inicia sesión como `test@example.com` / `password` (rol `administrator`, sembrado en la Fase 7) y entra a "Empleados" desde el menú superior.

## 9. Pendiente / fuera de alcance de esta fase

- Filtros y búsqueda en el listado: se dejan para la Fase 40 (Diseño visual), junto con el resto de la identidad "Enterprise SaaS".
- Vincular un `Employee` a un `User` existente (campo `user_id`) desde el formulario: el campo existe en el modelo/migración pero el formulario todavía no lo expone como selector — se agregará cuando se trabaje la gestión de usuarios/invitaciones, para no adivinar ese flujo ahora.

---

**Fase 8 completada y verificada con casos reales (creación, edición persistida en BD, bloqueo por rol, soft delete). Quedo a la espera de tu "CONTINUAR" para pasar a la Fase 9 (Proyectos).**
