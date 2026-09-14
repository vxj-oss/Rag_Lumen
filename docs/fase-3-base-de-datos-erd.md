# FASE 3 — Base de datos y ERD

> Estado: propuesta para revisión. Todavía **no** se generan migraciones ni se ejecuta ningún comando — eso ocurre a partir de la Fase 4 (instalación de Laravel) y se materializa tabla por tabla en la fase de cada módulo (empleados, proyectos, tareas, etc.).

## 1. Qué vamos a construir en esta fase

El **diseño completo a nivel de columna** de cada tabla (tipo de dato, nulabilidad, valores por defecto, claves foráneas, índices, únicos, soft deletes) y el **ERD** con las relaciones y su comportamiento ante borrado (`cascade` / `set null` / `restrict`).

## 2. Por qué

Definir esto antes de escribir migraciones evita dos problemas típicos: (a) migraciones que se van corrigiendo a medias conforme se descubren requisitos, y (b) decisiones de borrado en cascada tomadas "sobre la marcha" que terminan borrando información que debía conservarse (por ejemplo, historial de auditoría).

## 3. Convenciones generales

- Motor: MySQL 8.x, charset `utf8mb4`, collation `utf8mb4_unicode_ci`.
- Toda tabla propia tiene `id` `BIGINT UNSIGNED AUTO_INCREMENT` como PK (convención Eloquent).
- `timestamps` = `created_at` + `updated_at` (`TIMESTAMP NULL`), salvo que se indique lo contrario.
- Las tablas de **log inmutable** (`task_progress_updates`, `project_status_history`, `audit_logs`, `ai_messages`) solo tienen `created_at` — no tiene sentido un `updated_at` en un registro que nunca se edita.
- `ENUM` se usa para catálogos cerrados y estables definidos en este documento (estados, prioridades, tipos). Si un valor nuevo requiere cambiar el ENUM vía migración, se acepta ese costo a cambio de validación a nivel de base de datos.
- `JSON` se usa solo para metadata verdaderamente variable que no se filtra ni se ordena en SQL (si algo se necesita para un `WHERE`/`ORDER BY` frecuente, va en columna propia).
- `Soft deletes` (`deleted_at`) se aplican donde "eliminar" en la práctica significa "archivar sin perder historial" (`employees`, `projects`, `tasks`, `rag_documents`, comentarios). No se aplican en tablas de log puro (ya inmutables por diseño) ni en tablas pivote/pequeñas donde no aporta valor.

---

## 4. Tablas de autenticación y autorización

### `users` (estándar de Laravel, sin columnas adicionales)
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| name | varchar(255) | |
| email | varchar(255) | unique |
| email_verified_at | timestamp nullable | |
| password | varchar(255) | |
| remember_token | varchar(100) nullable | |
| timestamps | | |

### `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
Generadas y gestionadas por `spatie/laravel-permission` (migración propia del paquete). No se modifican manualmente; se documentan en la Fase 7 (Roles y permisos) cuando se instale el paquete.

---

## 5. Dominio: Empleados

### `employees`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK → users.id | nullable, **unique**, `on delete set null` |
| first_name | varchar(100) | |
| last_name | varchar(100) | |
| email | varchar(150) | unique |
| phone | varchar(30) | nullable |
| position | varchar(100) | nullable ("cargo") |
| specialty | enum | backend, frontend, fullstack, ux_ui, graphic_design, marketing, seo, advertising, project_manager, other |
| status | enum | active, inactive, on_leave — default `active` |
| hire_date | date | nullable |
| timestamps + deleted_at | | |

Índices: `unique(email)`, `unique(user_id)`, `index(specialty)`, `index(status)`.

---

## 6. Dominio: Proyectos

### `projects`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| code | varchar(30) | **unique** |
| name | varchar(150) | |
| description | text | nullable |
| type | enum | software_development, web_development, ux_ui_design, graphic_design, digital_marketing, seo, advertising, branding, campaign, other |
| client | varchar(150) | nullable |
| start_date | date | |
| estimated_end_date | date | |
| actual_end_date | date | nullable |
| status | enum | planning, in_progress, review, blocked, paused, completed, cancelled — default `planning` |
| priority | enum | low, medium, high, critical — default `medium` |
| responsible_employee_id | bigint FK → employees.id | nullable, `on delete set null` |
| budget | decimal(12,2) | nullable |
| risk_score | tinyint unsigned | nullable — **caché** del último cálculo de `ProjectRiskService` |
| risk_level | enum(low,medium,high,critical) | nullable — caché correspondiente |
| risk_calculated_at | datetime | nullable |
| observations | text | nullable |
| timestamps + deleted_at | | |

Índices: `unique(code)`, `index(status)`, `index(priority)`, `index(responsible_employee_id)`, `index(risk_level)`.

> **Por qué cachear `risk_score`/`risk_level`**: el dashboard y los listados necesitan **ordenar y filtrar** proyectos por riesgo frecuentemente; recalcular el score de todos los proyectos en cada listado sería costoso. Se guarda como caché actualizado por evento/job (Fase 1 §4), pero `ProjectRiskService` sigue siendo la única fuente de verdad del cálculo — estas columnas nunca se escriben a mano, solo el servicio las actualiza.

> **`progress_percentage` deliberadamente NO existe como columna** en `projects`: se calcula siempre al vuelo con `ProjectMetricsService` a partir de las tareas (ver Fase 1 §11), porque cambia constantemente y cachearlo duplicaría una fuente de verdad de bajo costo de cálculo.

### `project_members` (pivote)
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| project_id | bigint FK → projects.id | `on delete cascade` |
| employee_id | bigint FK → employees.id | `on delete cascade` |
| role_in_project | varchar(50) | ej. lead, developer, designer, contributor |
| assigned_at | date | |
| left_at | date | nullable |
| status | enum(active,inactive) | default `active` |
| timestamps | | |

Índices: `unique(project_id, employee_id)`, `index(employee_id)`.

### `project_comments`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| project_id | bigint FK → projects.id | `on delete cascade` |
| user_id | bigint FK → users.id | nullable, `on delete set null` |
| content | text | |
| is_relevant_for_rag | boolean | default `false` |
| timestamps + deleted_at | | |

Índice: `index(project_id)`, `index(is_relevant_for_rag)`.

### `project_status_history`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| project_id | bigint FK → projects.id | `on delete cascade` |
| previous_status | varchar(30) | nullable (nulo en el primer registro) |
| new_status | varchar(30) | |
| changed_by | bigint FK → users.id | nullable, `on delete set null` |
| reason | text | nullable |
| created_at | | (sin `updated_at`, es un log inmutable) |

Índice: `index(project_id)`.

---

## 7. Dominio: Tareas

### `tasks`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| project_id | bigint FK → projects.id | `on delete cascade` |
| assigned_to | bigint FK → employees.id | nullable, `on delete set null` |
| created_by | bigint FK → users.id | nullable, `on delete set null` |
| title | varchar(200) | |
| description | text | nullable |
| status | enum | pending, in_progress, review, blocked, completed, cancelled — default `pending` |
| priority | enum(low,medium,high,critical) | default `medium` |
| start_date | date | nullable |
| due_date | date | nullable |
| completed_at | datetime | nullable |
| progress_percentage | tinyint unsigned | default `0` |
| estimated_hours | decimal(6,2) | nullable |
| actual_hours | decimal(6,2) | nullable |
| blocked_reason | text | nullable — se completa cuando `status = blocked` |
| timestamps + deleted_at | | |

Índices: `index(project_id)`, `index(assigned_to)`, `index(status)`, `index(priority)`, `index(due_date)`.

> `progress_percentage` **sí** vive en `tasks` (a diferencia de `projects`) porque es el dato primario que el usuario ingresa directamente; el de `projects` es derivado y por eso no se almacena de la misma forma.

### `task_dependencies`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| task_id | bigint FK → tasks.id | `on delete cascade` |
| depends_on_task_id | bigint FK → tasks.id | `on delete cascade` |
| timestamps | | |

Índices: `unique(task_id, depends_on_task_id)`, `index(depends_on_task_id)`. Restricción a nivel de aplicación (Form Request): `task_id != depends_on_task_id` y sin ciclos (A depende de B que depende de A).

### `task_progress_updates`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| task_id | bigint FK → tasks.id | `on delete cascade` |
| user_id | bigint FK → users.id | nullable, `on delete set null` |
| previous_percentage | tinyint unsigned | |
| new_percentage | tinyint unsigned | |
| comment | text | nullable |
| created_at | | (sin `updated_at`) |

Índices: `index(task_id)`, `index(created_at)`.

### `task_comments`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| task_id | bigint FK → tasks.id | `on delete cascade` |
| user_id | bigint FK → users.id | nullable, `on delete set null` |
| content | text | |
| is_relevant_for_rag | boolean | default `false` |
| timestamps + deleted_at | | |

Índices: `index(task_id)`, `index(is_relevant_for_rag)`.

---

## 8. Dominio: RAG

### `rag_documents`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| project_id | bigint FK → projects.id | nullable, `on delete set null` |
| uploaded_by | bigint FK → users.id | nullable, `on delete set null` |
| title | varchar(200) | |
| file_name | varchar(255) | |
| file_path | varchar(500) | |
| mime_type | varchar(100) | |
| source_type | enum(pdf,docx,txt,manual,other) | |
| status | enum(pending,processing,processed,failed) | default `pending` |
| failure_reason | text | nullable |
| metadata | json | nullable |
| timestamps + deleted_at | | |

Índices: `index(project_id)`, `index(status)`.

> `project_id` nullable a propósito: permite documentos de conocimiento general de la empresa (manuales, políticas) no atados a un proyecto específico.

### `rag_chunks`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| document_id | bigint FK → rag_documents.id | `on delete cascade` |
| content | text | |
| chunk_index | int unsigned | |
| metadata | json | nullable (ej. página, sección) |
| vector_reference | json | nullable — embedding (MVP) o referencia externa si se migra de motor vectorial |
| timestamps | | |

Índices: `index(document_id)`, `unique(document_id, chunk_index)`.

---

## 9. Dominio: Agente IA

### `ai_conversations`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK → users.id | `on delete cascade` |
| project_id | bigint FK → projects.id | nullable, `on delete set null` |
| title | varchar(200) | nullable |
| timestamps | | |

Índice: `index(user_id)`.

### `ai_messages`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| conversation_id | bigint FK → ai_conversations.id | `on delete cascade` |
| role | enum(user,assistant) | |
| content | longtext | |
| tools_used | json | nullable |
| retrieved_documents | json | nullable |
| metadata | json | nullable |
| processing_time_ms | int unsigned | nullable |
| created_at | | (sin `updated_at`) |

Índice: `index(conversation_id)`.

---

## 10. Auditoría y notificaciones

### `audit_logs`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK → users.id | nullable, `on delete set null` |
| action | varchar(100) | ej. `project.created`, `task.progress_updated`, `ai.chat_query` |
| auditable_type | varchar(150) | nullable (polimórfico) |
| auditable_id | bigint unsigned | nullable (polimórfico) |
| description | varchar(255) | nullable |
| metadata | json | nullable |
| ip_address | varchar(45) | nullable |
| created_at | | (sin `updated_at`) |

Índices: `index(user_id)`, `index(action)`, `index(auditable_type, auditable_id)`.

### `notifications`
Se usa la tabla estándar de Laravel (`uuid` PK, `type`, `notifiable_type`, `notifiable_id`, `data` json, `read_at`, timestamps), generada con el comando nativo del framework en la fase de Alertas (Fase 18) — no se diseña una tabla propia (ver Fase 1 §23, decisión de reutilizar el sistema nativo).

---

## 11. ERD — relaciones y comportamiento ante borrado

```
users 1───0..1 employees                              (employees.user_id, SET NULL)
employees 1───N project_members N───1 projects         (CASCADE en ambos lados del pivote)
projects 1───N tasks                                    (CASCADE)
projects 1───N project_comments                         (CASCADE)
projects 1───N project_status_history                   (CASCADE)
projects 0..1───N rag_documents                         (SET NULL — documento puede quedar "huérfano" de proyecto)
projects 0..1───N ai_conversations                       (SET NULL)
employees 0..1───N tasks (assigned_to)                   (SET NULL)
employees 0..1───N projects (responsible_employee_id)    (SET NULL)
users 0..1───N tasks (created_by)                        (SET NULL)
tasks 1───N task_progress_updates                        (CASCADE)
tasks 1───N task_comments                                (CASCADE)
tasks N───N tasks (task_dependencies)                    (CASCADE en ambos FKs)
rag_documents 1───N rag_chunks                            (CASCADE)
users 1───N ai_conversations                              (CASCADE)
ai_conversations 1───N ai_messages                        (CASCADE)
users 0..1───N audit_logs                                 (SET NULL — el log sobrevive aunque se borre el usuario)
```

**Regla aplicada consistentemente**: se usa `CASCADE` cuando el registro hijo **no tiene sentido sin el padre** (un chunk sin documento, una actualización de avance sin tarea). Se usa `SET NULL` cuando el hijo **debe sobrevivir** al padre por valor histórico/de auditoría (un log de auditoría no debe desaparecer porque se borró el usuario; un documento RAG de conocimiento general no debe desaparecer porque se archivó el proyecto que lo originó).

---

## 12. Qué NO se modela como tabla (y por qué)

- **Especialidades, tipos, estados, prioridades**: son `ENUM` fijos definidos en este documento, no catálogos editables en caliente. Si en el futuro necesitan ser editables por el usuario, se migran a tabla en ese momento (no antes, para no sobreingeniería).
- **`alerts` propia**: se usa el sistema nativo de notificaciones de Laravel (sección 10).
- **Snapshots de métricas por proyecto**: se reconstruyen desde `task_progress_updates` (ver Fase 1 §11); no hay tabla `project_metrics_snapshots` en el MVP.

---

## 13. Comandos

Ninguno todavía. Las migraciones reales (`php artisan make:migration ...`) se generarán en la Fase 4 (esqueleto de `users`) y luego una por módulo: empleados (Fase 8), proyectos (Fase 9), miembros (Fase 10), tareas (Fase 11), dependencias (Fase 12), avances (Fase 13), RAG (Fases 20-24), agente (Fase 27), auditoría (Fase 32).

## 14. Cómo verificar esta fase

Revisar que cada tabla de la sección 9 del documento de Fase 1 tenga aquí su definición completa de columnas, y que cada relación declarada en Fase 1 §10 tenga aquí su comportamiento de borrado explícito (no debe quedar ninguna FK "implícita").

## 15. Errores comunes a anticipar (para cuando se escriban las migraciones)

- **Olvidar `on delete set null` en columnas `nullable`**: Laravel/MySQL no lo infieren solos; si no se especifica, el borrado del padre falla por restricción de FK (comportamiento por defecto `RESTRICT`), lo cual puede ser deseable en algunos casos pero debe ser una decisión explícita, no un olvido.
- **Definir `enum` con valores distintos entre la migración y las constantes PHP** (ej. `Project::STATUS_IN_PROGRESS`): se mantendrá una única fuente de verdad en un Enum de PHP 8.1+ (`App\Support\Enums\...`) referenciado tanto por la migración como por las validaciones, para que nunca diverjan.
- **Orden de migraciones**: las tablas con FK deben migrarse después de sus tablas referenciadas (ej. `employees` antes que `projects`, `projects` antes que `tasks`). Se planificará el orden exacto en la fase de cada módulo.

---

**Quedo a la espera de tu confirmación ("CONTINUAR") o de los cambios que quieras hacer antes de avanzar a la Fase 4 (Creación e instalación de Laravel 13).**
