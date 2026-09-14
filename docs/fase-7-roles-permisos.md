# FASE 7 — Roles y permisos

## 1. Qué se construyó

Infraestructura de roles con **spatie/laravel-permission v8.3.0** (confirmado compatible con Laravel 13), con los 4 roles definidos en la Fase 1 §7, integrada al modelo `User` y a las rutas mediante middleware, verificada con un caso real de bloqueo/permiso.

## 2. Por qué spatie/laravel-permission

Es el paquete estándar del ecosistema Laravel para roles/permisos, se integra nativamente con `Gate`/`@can`/Policies sin configuración adicional, y evita reinventar las tablas pivote de roles-permisos-usuarios (que ya se habían dejado fuera del diseño propio en la Fase 3 §4, precisamente para usar este paquete).

## 3. Alcance de esta fase (y qué queda para después)

Esta fase deja lista la **infraestructura** de roles: el paquete instalado, los 4 roles creados, el modelo `User` habilitado, y los middlewares de autorización funcionando y probados. **No** se definen todavía permisos granulares (`view-projects`, `manage-tasks`, etc.) ni Policies por modelo, porque esos modelos (`Project`, `Task`, `Employee`) no existen aún — se agregarán en la fase de cada módulo (Fase 8 en adelante), momento en el que además tendrá sentido decidir la lista final de permisos en vez de adivinarla ahora.

## 4. Comandos ejecutados

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate --force
php artisan db:seed --force
```

## 5. Archivos creados

- [config/permission.php](../Proyecto%20PPP2/config/permission.php) — configuración del paquete (sin cambios sobre el default; usa el guard `web`).
- `database/migrations/2026_09_11_054505_create_permission_tables.php` — tablas `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (gestionadas por el paquete, tal como se documentó en la Fase 3 §4).
- **`app/Support/Enums/RoleName.php`** — enum PHP con los 4 roles como única fuente de verdad (evita strings mágicos repetidos, mismo criterio anti-error que se dejó anotado en la Fase 3 §15 para los `ENUM` de base de datos):
  ```php
  enum RoleName: string
  {
      case Administrator = 'administrator';
      case Manager = 'manager';
      case ProjectLead = 'project_lead';
      case Employee = 'employee';

      public function label(): string { /* Administrador, Gerente, Líder de Proyecto, Empleado */ }
  }
  ```
- **`database/seeders/RoleSeeder.php`** — crea los 4 roles (`firstOrCreate`, idempotente).

## 6. Archivos modificados

- **`app/Models/User.php`**: se agregó el trait `Spatie\Permission\Traits\HasRoles`.
- **`database/seeders/DatabaseSeeder.php`**: ahora llama a `RoleSeeder` primero y asigna el rol `administrator` al usuario de prueba (`test@example.com`).
- **`bootstrap/app.php`**: se registraron manualmente los alias de middleware `role`, `permission`, `role_or_permission`. **Importante**: en Laravel 13 (sin `Kernel.php`), spatie **no** los registra automáticamente para el nuevo estilo de arranque en `bootstrap/app.php` — hay que darlos de alta explícitamente con `$middleware->alias([...])`. Esto no está documentado de forma obvia y es una fuente común de error ("Target class [role] does not exist").

## 7. Por qué los nombres de rol están en inglés (`administrator`, no `administrador`)

Por consistencia con la Fase 1 §41 (código en inglés, interfaz en español): el valor guardado en la base de datos es el identificador de código; `RoleName::label()` da la etiqueta en español para cuando se construya la pantalla de gestión de usuarios/roles.

## 8. Verificación realizada (con casos reales de bloqueo, no solo de éxito)

Como `hasRole()`/`role:` dependen del kernel HTTP (no se resuelven completamente dentro de `tinker`), la verificación se hizo con peticiones HTTP reales sobre una ruta temporal protegida con `middleware(['auth', 'role:administrator'])`, creada solo para esta prueba y **eliminada al terminar**:

| Caso | Resultado |
|---|---|
| Roles creados en BD tras el seeder | `administrator`, `manager`, `project_lead`, `employee` — verificado por consulta SQL directa |
| `test@example.com` (rol `administrator`) → ruta protegida con `role:administrator` | **200 OK** |
| Usuario nuevo con rol `employee` → misma ruta protegida con `role:administrator` | **403 Forbidden** |
| `$user->hasRole('administrator')` vía tinker | `true` |
| `$user->hasRole('employee')` vía tinker (mismo usuario) | `false` |

Esto confirma que el sistema no solo "no da error", sino que **efectivamente autoriza y deniega** según el rol — que es el requisito real de la Fase 1 §7/§18 (RAG y funciones sensibles deben respetar permisos).

## 9. Hallazgo importante para las próximas fases

Los alias de middleware de Spatie deben registrarse a mano en `bootstrap/app.php` en Laravel 13 (ver sección 6). Cualquier otro paquete que en su documentación asuma la existencia de `app/Http/Kernel.php` (guías escritas para Laravel ≤10) necesitará el mismo ajuste manual — se revisará caso por caso en cada fase que instale un paquete nuevo.

## 10. Cómo probarlo tú mismo

```bash
php artisan tinker
>>> $u = \App\Models\User::first();
>>> $u->assignRole('manager');
>>> $u->hasRole('manager'); // true
>>> $u->getRoleNames(); // Collection ['manager']
```

Para probar el middleware en una ruta real, protégela temporalmente con `->middleware('role:manager')` y visítala autenticado con un usuario con y sin ese rol.

## 11. Errores comunes

| Error | Causa | Solución |
|---|---|---|
| `Target class [role] does not exist` | Alias de middleware no registrado (típico si se sigue una guía para Laravel ≤10) | Agregar el bloque de `$middleware->alias([...])` en `bootstrap/app.php` como se hizo aquí |
| `hasRole()` siempre `false` aunque el rol se asignó | Se probó dentro de `tinker` esperando el comportamiento de rutas HTTP, o el usuario no recargó su relación de roles tras `assignRole()` en el mismo request | Volver a cargar el modelo (`$user->refresh()`) o probar con una petición HTTP real |
| `There is no role named administrator for guard web` | Se seedearon roles con un guard distinto al que usa `Auth` | Confirmar `guard_name => 'web'` en el seeder (ya está así) |

---

**Fase 7 completada y verificada con casos reales de autorización (200 vs 403). Quedo a la espera de tu "CONTINUAR" para pasar a la Fase 8 (Empleados).**
