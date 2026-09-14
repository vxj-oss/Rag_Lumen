# FASE 1 — Arquitectura General
## Agente Inteligente con RAG para la Toma de Decisiones en una Empresa de Marketing

> Estado: propuesta para revisión. No se ha generado código, migraciones ni comandos de instalación.
> Stack objetivo: Laravel 13.x · PHP 8.3+ · MySQL 8.x · Blade · Vite · Tailwind CSS · JavaScript (sin frameworks SPA).

---

## 1. Arquitectura general

El sistema se organiza en **cinco capas** con responsabilidades estrictamente separadas. La IA nunca sustituye la lógica de negocio: solo la interpreta y la comunica.

| Capa | Responde a | Tecnología | Naturaleza |
|---|---|---|---|
| 1. Datos empresariales | "¿Qué ocurrió?" | MySQL + Eloquent | Determinística |
| 2. Motor de métricas y decisiones | "¿Cómo está funcionando?" / "¿Qué señales hay?" | Servicios PHP puros | Determinística |
| 3. RAG (recuperación de conocimiento) | "¿Qué información contextual existe?" | Chunking + Embeddings + Vector Store | Determinística en el retrieval, semántica en el matching |
| 4. Agente IA | "¿Qué significa esto y qué debería hacer la empresa?" | LLM + Tools | Generativa, pero **anclada** a las capas 1-3 |
| 5. Presentación | Interacción con el usuario | Blade + Tailwind + JS modular | — |

Principio rector: **el LLM nunca calcula, nunca inventa datos y nunca decide por sí mismo qué es "riesgo alto"**. Solo redacta y contextualiza lo que las capas 1-3 ya determinaron de forma verificable.

---

## 2. Diagrama textual

```
                        ┌────────────────────────────┐
                        │          USUARIO            │
                        └──────────────┬──────────────┘
                                       │ HTTP (Blade / API)
                        ┌──────────────▼──────────────┐
                        │           LARAVEL            │
                        │  Controllers / Requests /     │
                        │  Policies / Middleware        │
                        └──────────────┬──────────────┘
                                       │
        ┌──────────────────────────────┼──────────────────────────────┐
        │                              │                              │
┌───────▼────────┐           ┌─────────▼─────────┐          ┌─────────▼─────────┐
│  CAPA 1: DATOS  │           │  CAPA 2: MOTOR DE  │          │   CAPA 3: RAG      │
│  EMPRESARIALES  │◄──────────┤    DECISIONES      │          │ (conocimiento no   │
│  (MySQL)        │  lee      │                    │          │  estructurado)     │
│                 │           │ ProjectMetricsService         │                    │
│ Proyectos       │           │ ProjectRiskService │          │ Documentos         │
│ Tareas          │           │ ProjectDecisionService        │ Chunks             │
│ Empleados       │           │                    │          │ Vector Store       │
│ Avances         │           │ → JSON estructurado│          │ Retriever          │
│ Comentarios     │           │   (riesgo, señales,│          │                    │
│ Incidencias     │           │    razones)        │          │ → fragmentos       │
└─────────────────┘           └─────────┬──────────┘          │   relevantes con   │
                                         │                     │   fuente y permisos│
                                         │                     └─────────┬─────────┘
                                         │                               │
                                         └───────────────┬───────────────┘
                                                          ▼
                                            ┌──────────────────────────┐
                                            │      CAPA 4: AGENTE IA     │
                                            │  ProjectDecisionAgent      │
                                            │  (orquesta Tools + RAG)    │
                                            │                            │
                                            │  Datos → Interpretación →  │
                                            │  Recomendación             │
                                            └─────────────┬─────────────┘
                                                          ▼
                                            ┌──────────────────────────┐
                                            │  RESPUESTA CON TRAZABILIDAD │
                                            │  (datos usados + fuentes)  │
                                            └─────────────┬─────────────┘
                                                          ▼
                                                     USUARIO
```

---

## 3. Módulos

1. **Auth** — login, logout, recuperación de contraseña, perfil.
2. **Roles y permisos** — Administrador, Gerente, Líder de Proyecto, Empleado.
3. **Empleados** — CRUD, especialidades, estado.
4. **Proyectos** — CRUD, estados, prioridades, miembros.
5. **Tareas** — CRUD, dependencias, avances, bloqueos.
6. **Métricas** — cálculo de avance, cumplimiento, tendencias.
7. **Riesgo** — scoring determinístico por proyecto.
8. **Motor de decisiones** — señales estructuradas (HIGH_RISK, DELAYED, etc.).
9. **Dashboard** — vista ejecutiva con KPIs y gráficos.
10. **Alertas / notificaciones** — avisos accionables.
11. **RAG** — carga, procesamiento e indexado de documentos.
12. **Agente IA / Chat** — conversación con trazabilidad.
13. **Auditoría** — registro de acciones sensibles.
14. **API** — exposición REST de lo anterior.

Cada módulo es dueño de sus vistas, JS y CSS (ver sección 5), y de sus servicios en `app/`.

---

## 4. Flujo de información

```
Empleado actualiza avance de tarea
        │
        ▼
task_progress_updates (historial inmutable)
        │
        ▼
Evento TaskProgressUpdated
        │
        ├──► Recalcular métricas del proyecto (ProjectMetricsService)
        │
        ├──► Recalcular riesgo (ProjectRiskService)
        │
        └──► Motor de decisiones evalúa señales (ProjectDecisionService)
                    │
                    ├──► Si cruza umbral crítico → Notificación
                    │
                    └──► Disponible para el Agente IA cuando se le consulte
```

El RAG es un flujo **paralelo e independiente**: documentos se procesan de forma asíncrona y no participan del cálculo de métricas/riesgo, solo aportan contexto cualitativo cuando el agente lo necesita.

---

## 5. Estructura completa de carpetas propuesta (`resources/`)

```
resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php              # layout autenticado (sidebar + navbar)
│   │   └── guest.blade.php            # layout auth (login, password reset)
│   │
│   ├── components/
│   │   ├── layouts/     (sidebar.blade.php, navbar.blade.php)
│   │   ├── ui/           (card, button, badge, modal, alert, progress-bar, stat-card)
│   │   ├── forms/        (input, select, textarea, date-picker)
│   │   ├── tables/       (table, table-row, pagination)
│   │   └── charts/       (bar-chart, line-chart, donut-chart — wrappers JS)
│   │
│   ├── auth/
│   │   ├── html/  (login.blade.php, forgot-password.blade.php, reset-password.blade.php)
│   │   ├── js/    (login.js)
│   │   └── css/   (login.css)
│   │
│   ├── profile/
│   │   ├── html/ (edit.blade.php)
│   │   ├── js/   (profile.js)
│   │   └── css/  (profile.css)
│   │
│   ├── dashboard/
│   │   ├── html/ (index.blade.php)
│   │   ├── js/   (dashboard.js)
│   │   └── css/  (dashboard.css)
│   │
│   ├── employees/
│   │   ├── html/ (index, create, edit, show .blade.php)
│   │   ├── js/   (employees.js, employee-form.js)
│   │   └── css/  (employees.css)
│   │
│   ├── projects/
│   │   ├── html/ (index, create, edit, show .blade.php)
│   │   ├── js/   (projects.js, project-form.js, project-show.js)
│   │   └── css/  (projects.css)
│   │
│   ├── tasks/
│   │   ├── html/ (index, create, edit, show .blade.php)
│   │   ├── js/   (tasks.js, task-form.js, task-show.js)
│   │   └── css/  (tasks.css)
│   │
│   ├── alerts/
│   │   ├── html/ (index.blade.php)
│   │   ├── js/   (alerts.js)
│   │   └── css/  (alerts.css)
│   │
│   ├── reports/
│   │   ├── html/ (index.blade.php)
│   │   ├── js/   (reports.js)
│   │   └── css/  (reports.css)
│   │
│   ├── ai/
│   │   ├── html/ (index.blade.php)      # chat
│   │   ├── js/   (ai.js, ai-chat.js)
│   │   └── css/  (ai.css)
│   │
│   └── rag/
│       ├── html/ (index.blade.php, documents.blade.php)
│       ├── js/   (rag.js, documents.js)
│       └── css/  (rag.css)
│
├── js/
│   ├── app.js                         # solo bootstrap: Alpine/plugins globales, nada de lógica de página
│   └── shared/                        # utilidades usadas por varios módulos (http.js, toast.js, formatters.js)
│
└── css/
    └── app.css                        # entrada de Tailwind (@tailwind base/components/utilities)
```

### Cómo encuentra Laravel estas vistas

Laravel resuelve `view('projects.html.index')` mapeando cada punto a un separador de carpeta dentro de `resources/views/`. No requiere configuración adicional: `projects.html.index` → `resources/views/projects/html/index.blade.php`. Es la misma resolución "dot notation" que ya usa Laravel para vistas anidadas; anidar `html/js/css` dentro de cada módulo no rompe nada, simplemente agrega un nivel más en la ruta con puntos.

Los **componentes** (`resources/views/components/...`) se autodescubren: `resources/views/components/ui/card.blade.php` queda disponible como `<x-ui.card>` sin registro manual, por la convención estándar de Blade components.

---

## 6. Arquitectura backend (`app/`)

```
app/
├── Models/                    # Eloquent: Project, Task, Employee, RagDocument, etc.
│
├── Http/
│   ├── Controllers/
│   │   ├── Web/                # controladores que devuelven vistas Blade
│   │   └── Api/V1/             # controladores que devuelven JSON
│   ├── Requests/               # Form Requests (validación)
│   └── Middleware/
│
├── Policies/                   # autorización por modelo (ProjectPolicy, TaskPolicy...)
│
├── Services/
│   ├── ProjectMetricsService.php
│   ├── ProjectRiskService.php
│   └── ProjectDecisionService.php
│
├── Actions/                    # operaciones puntuales que no ameritan un Service completo
│                                # ej: RecordTaskProgressAction, AssignEmployeeToProjectAction
│
├── Jobs/
│   ├── ProcessRagDocument.php
│   ├── GenerateDocumentEmbeddings.php
│   └── RecalculateProjectRiskAndMetrics.php
│
├── Events/  &  Listeners/
│   ├── TaskProgressUpdated → RecalculateProjectMetrics, RecalculateProjectRisk
│   ├── ProjectStatusChanged → LogProjectStatusHistory
│   └── TaskBlocked / TaskCompleted → (disparan recálculo si aplica)
│
├── AI/
│   ├── Agents/
│   │   └── ProjectDecisionAgent.php
│   ├── Tools/
│   │   ├── GetProjectStatusTool.php
│   │   ├── GetProjectRiskTool.php
│   │   ├── GetDelayedProjectsTool.php
│   │   ├── GetEmployeeWorkloadTool.php
│   │   ├── GetBlockedTasksTool.php
│   │   ├── GetProjectMetricsTool.php
│   │   ├── SearchKnowledgeBaseTool.php
│   │   └── ...
│   └── Prompts/
│       └── plantillas de sistema/contexto (texto, no lógica)
│
├── RAG/
│   ├── Services/
│   │   ├── DocumentIngestionService.php
│   │   └── ContextBuilderService.php
│   ├── Loaders/          (PdfDocumentLoader, DocxDocumentLoader, TxtDocumentLoader)
│   ├── Chunkers/         (RecursiveTextChunker)
│   ├── Embeddings/       (EmbeddingServiceInterface + implementación concreta)
│   ├── Retrievers/       (RetrieverInterface + implementación concreta, con filtro de permisos)
│   ├── VectorStores/     (VectorStoreInterface + MySqlVectorStore para el MVP)
│   └── Prompts/          (formato de "contexto recuperado" que se inyecta al LLM)
│
└── Support/
    └── helpers/formatters/enums compartidos que no encajan en otra carpeta
```

**Por qué estos patrones y no otros** (punto 4 del prompt):

- **Service Layer** (`ProjectMetricsService`, `ProjectRiskService`, `ProjectDecisionService`): se justifica porque estas reglas se reutilizan desde controladores web, API, jobs programados y el agente IA. Ponerlas en el modelo o el controlador obligaría a duplicar lógica en 4 lugares.
- **Interfaces** (`EmbeddingServiceInterface`, `VectorStoreInterface`, `RetrieverInterface`, `DocumentLoaderInterface`): se justifican explícitamente por el requisito de no atarse a un proveedor de IA/vector store. Sin esta interfaz, cambiar de proveedor implicaría reescribir código de negocio.
- **Events/Listeners**: solo para el caso real de desacoplamiento: cuando una tarea cambia, varias cosas *independientes* deben reaccionar (métricas, riesgo, historial). Sin eventos, cada controlador tendría que conocer y llamar manualmente a cada uno de esos efectos.
- **Actions**: reservadas para operaciones puntuales de un solo paso (ej. "registrar avance de tarea") que no ameritan una clase de servicio completa pero tampoco deben vivir en el controlador.
- **NO se usa Repository Pattern**: Eloquent ya es la capa de abstracción de datos; envolverlo en repositorios no aporta valor aquí porque no hay planes de cambiar de ORM, y MySQL es fijo para datos empresariales.
- **NO se usa DTOs formales**: Form Requests + arrays/colecciones tipadas de Eloquent son suficientes para este tamaño de proyecto; introducir DTOs en cada capa sería sobreingeniería para un sistema académico/profesional de este alcance.
- **NO se usa CQRS**: la complejidad de lectura/escritura no lo justifica; los Services ya separan "cálculo" de "persistencia".

---

## 7. Arquitectura frontend

- **Blade** para todo el marcado, con componentes reutilizables (`<x-ui.card>`, `<x-ui.stat-card>`, `<x-ui.table>`, etc.) para evitar HTML duplicado.
- **Tailwind CSS** como único sistema de estilos, configurado una vez en `resources/css/app.css` y `tailwind.config.js` (con `content` apuntando también a `resources/views/**/html/*.blade.php` y `resources/views/**/*.blade.php`, ya que las vistas viven en subcarpetas `html/`).
- **JavaScript modular**: cada módulo tiene su propio archivo de entrada (`projects.js`, `tasks.js`, `dashboard.js`, `ai.js`, etc.) en vez de un `app.js` monolítico. `resources/js/app.js` solo inicializa librerías globales (ej. Alpine.js si se usa) y utilidades compartidas.
- **Vite** se configura con **múltiples entradas**, una por vista/módulo, en vez de un único bundle:

```js
// vite.config.js (conceptual, se detalla en la fase de instalación)
laravel({
  input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/views/auth/js/login.js',
    'resources/views/dashboard/js/dashboard.js',
    'resources/views/projects/js/projects.js',
    'resources/views/projects/js/project-form.js',
    'resources/views/tasks/js/tasks.js',
    'resources/views/ai/js/ai.js',
    // ...una entrada por cada js/ de módulo
  ],
  refresh: true,
})
```

Cada Blade view carga únicamente sus propios assets con `@vite([...])`, así el navegador nunca descarga JS de módulos que no está usando. Vite no exige que las entradas vivan en `resources/js`; cualquier ruta dentro de `resources/` es válida como entry point.

---

## 8. Arquitectura de base de datos

- **MySQL 8.x** es la única base de datos empresarial. Usa `utf8mb4`, claves foráneas reales (no simuladas en la aplicación), índices en columnas de filtrado frecuente (`status`, `priority`, `project_id`, `due_date`) y `JSON` solo para metadata verdaderamente flexible (no para datos que se consultan/filtran, esos van en columnas normales).
- **Soft deletes** en `projects` y `tasks` (para no perder historial si se "eliminan"), no en tablas de log puro (`task_progress_updates`, `audit_logs`, `project_status_history`) que son inmutables por diseño.
- El **vector store del RAG vive lógicamente separado** de los datos empresariales aunque, en el MVP, ambos usen el mismo motor MySQL físico (ver sección 14). Esto es clave: si mañana el vector store migra a Qdrant/pgvector, ninguna tabla empresarial se toca.

---

## 9. Tablas propuestas

| Tabla | Propósito |
|---|---|
| `users` | Autenticación (Laravel estándar) |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Gestionadas por el paquete de roles/permisos (ver sección 21) |
| `employees` | Perfil laboral de cada persona |
| `projects` | Proyectos gestionados por la empresa |
| `project_members` | Pivote proyecto↔empleado con rol, fechas y estado |
| `tasks` | Tareas de cada proyecto |
| `task_dependencies` | Dependencias entre tareas |
| `task_progress_updates` | Historial inmutable de avances |
| `task_comments` | Comentarios sobre tareas |
| `project_comments` | Comentarios sobre proyectos |
| `project_status_history` | Historial de cambios de estado de proyecto |
| `rag_documents` | Documentos subidos para el RAG |
| `rag_chunks` | Fragmentos indexables de cada documento |
| `ai_conversations` | Conversaciones con el agente |
| `ai_messages` | Mensajes de cada conversación |
| `audit_logs` | Bitácora de acciones sensibles |

**No se crean** tablas para "especialidades" o "prioridades" (son `enum`/constantes, no catálogos que cambien en tiempo real), ni una tabla `alerts` propia — se reutiliza el sistema de notificaciones nativo de Laravel (ver sección 10, "decisiones importantes").

---

## 10. Relaciones principales

```
users 1───0..1 employees
employees 1───N project_members N───1 projects
projects 1───N tasks
tasks 1───N task_progress_updates
tasks 1───N task_comments
tasks N───N tasks              (vía task_dependencies: depends_on)
projects 1───N project_comments
projects 1───N project_status_history
projects 1───N rag_documents (nullable: puede haber documentos generales, no atados a un proyecto)
rag_documents 1───N rag_chunks
users 1───N ai_conversations
ai_conversations 1───N ai_messages
users 1───N audit_logs (nullable: acciones del sistema pueden no tener usuario)
```

Detalles relevantes:
- `employees.user_id` es **nullable y único**: todo empleado con acceso al sistema tiene una fila en `users`, pero se admite (si hiciera falta) un registro de empleado sin cuenta.
- `project_members` guarda `role_in_project`, `assigned_at`, `left_at`, `status` — permite saber quién participa **hoy** y quién participó **antes**, sin borrar historial.
- `task_dependencies` es una relación N:N reflexiva sobre `tasks` (`task_id`, `depends_on_task_id`), con restricción para que una tarea no dependa de sí misma.
- `rag_chunks.vector_reference` es deliberadamente genérico (JSON o string) para no acoplar el esquema a un proveedor de embeddings específico (ver sección 14).

---

## 11. Arquitectura del motor de métricas

`ProjectMetricsService` es una clase de **funciones puras sobre datos ya persistidos** (no escribe estado por sí sola salvo cuando se le pide explícitamente cachear un snapshot). Calcula, por proyecto:

- Avance real = promedio ponderado del `progress_percentage` de sus tareas (ponderado por `estimated_hours` si están cargadas, si no, promedio simple).
- Avance esperado = función lineal del tiempo transcurrido entre `start_date` y `estimated_end_date` (0% al inicio, 100% en la fecha límite). Es una aproximación deliberadamente simple y explicable, no un modelo de curva S.
- Brecha = avance esperado − avance real.
- Conteos: tareas totales/completadas/pendientes/atrasadas/bloqueadas/próximas a vencer (ventana configurable, ej. 3 días).
- Horas estimadas vs reales.
- Velocidad de avance = variación de "avance real" entre dos snapshots recientes de `task_progress_updates` (tendencia: acelerando / estable / desacelerando).
- Cumplimiento de fechas = tareas completadas a tiempo / tareas completadas totales.

**Snapshots históricos de métricas**: se recomienda **no** almacenar un snapshot en cada request (se recalcula al vuelo, es barato), pero **sí** guardar un snapshot ligero cuando el `RecalculateProjectRiskAndMetrics` job corre (ej. diario, o al ocurrir un evento relevante), reutilizando `project_status_history`/una tabla de log si en el futuro se requiere graficar "evolución del avance" en el tiempo con precisión. Para el MVP, la gráfica de evolución puede construirse a partir de `task_progress_updates` (ya es historial real), sin tabla adicional.

---

## 12. Sistema de riesgo

`ProjectRiskService` calcula un **score de 0 a 100**, determinístico y explicable, a partir de cinco sub-puntajes normalizados (0-100) más un ajuste por prioridad:

| Componente | Peso | Qué mide |
|---|---|---|
| `DelayScore` | 25% | proporción de tareas atrasadas + severidad promedio del atraso (días) |
| `BlockedScore` | 20% | proporción de tareas bloqueadas + tiempo promedio bloqueadas |
| `ProgressGapScore` | 25% | diferencia entre avance esperado y avance real |
| `DeadlinePressureScore` | 20% | trabajo restante vs días restantes hasta la fecha límite |
| `WorkloadScore` | 10% | sobrecarga promedio de los empleados asignados al proyecto |

```
RiskScore = clamp(
    0.25 * DelayScore
  + 0.20 * BlockedScore
  + 0.25 * ProgressGapScore
  + 0.20 * DeadlinePressureScore
  + 0.10 * WorkloadScore
  + PriorityModifier
, 0, 100)

PriorityModifier: critical +8, high +4, medium 0, low -4
```

Cada sub-score es una función pura y testeable de forma aislada (ej. `DelayScore(overdue_ratio, avg_overdue_days)`), con sus propios topes de saturación (ej. un atraso promedio ≥14 días ya satura esa componente a máximo). Los pesos y topes se definen en un archivo de configuración (`config/risk.php`), no hardcodeados, para poder ajustarlos sin tocar lógica.

Clasificación (configurable): 0-30 Bajo · 31-60 Medio · 61-80 Alto · 81-100 Crítico.

---

## 13. Motor de decisiones

`ProjectDecisionService` consume la salida de los dos servicios anteriores y produce **datos estructurados**, nunca texto:

```json
{
  "project_id": 12,
  "risk_score": 87,
  "risk_level": "critical",
  "signals": ["HIGH_RISK", "DELAYED", "BLOCKED", "PROGRESS_GAP"],
  "reasons": [
    "6 tareas atrasadas de 20",
    "2 tareas bloqueadas",
    "avance 24 puntos porcentuales por debajo de lo esperado"
  ],
  "recommended_actions": [
    "Revisar las tareas bloqueadas",
    "Evaluar reasignación temporal de recursos"
  ]
}
```

Las señales (`HIGH_RISK`, `DELAYED`, `RESOURCE_OVERLOAD`, `BLOCKED`, `DEADLINE_RISK`, `ATTENTION_REQUIRED`, `HEALTHY`) se derivan por reglas simples de umbral sobre los datos de los dos servicios anteriores (ej. `signals[] = 'BLOCKED'` si `blocked_tasks_count > 0`). El agente IA consume exactamente este JSON; nunca decide por su cuenta si un proyecto está en riesgo.

---

## 14. Arquitectura RAG

```
Documento (PDF/DOCX/TXT)
   │  upload (guarda archivo + fila en rag_documents, status=pending)
   ▼
Job: ProcessRagDocument            ← en cola, no bloquea el request
   │
   ├─ DocumentLoader (según mime_type) → extrae texto plano
   ├─ Normalización (limpieza de saltos, encabezados repetidos, etc.)
   ├─ TextChunker (ventanas con solape, ej. 800 tokens / 100 de solape)
   ├─ EmbeddingService → vector por chunk
   └─ VectorStore → persiste chunk + vector, status=processed
```

**Interfaces desacopladas** (todas en `app/RAG/`):
`DocumentLoaderInterface`, `TextChunkerInterface` (opcional, o clase concreta única si solo hay una estrategia), `EmbeddingServiceInterface`, `VectorStoreInterface`, `RetrieverInterface`.

**Recomendación para el MVP — vector store**: implementar `MySqlVectorStore` guardando el embedding de cada chunk como `JSON` en `rag_chunks.vector_reference`, calculando similitud coseno en PHP al momento de recuperar (filtrando primero por permisos y, si aplica, por proyecto). Razones:
- No agrega infraestructura nueva (Qdrant/Pinecone/pgvector) a un proyecto académico que ya usa MySQL.
- El volumen esperado (decenas de documentos, cientos/pocos miles de chunks) hace viable comparar en memoria sin problema de rendimiento perceptible.
- El contrato `VectorStoreInterface` es el único punto que habría que reemplazar para migrar a un motor vectorial real en producción a mayor escala — nada más del sistema lo sabría.

**Embeddings y LLM**: no existe un paquete "oficial de Laravel" para esto. Se recomienda un cliente propio y delgado (`EmbeddingServiceInterface` / `LlmClientInterface`) que use el `Http` facade de Laravel para llamar a la API del proveedor configurado en `.env` (`AI_PROVIDER`, `AI_MODEL`, `EMBEDDING_MODEL`). Esto evita depender de un paquete de terceros de vida corta para algo tan central. (Existen paquetes de la comunidad para orquestación LLM en Laravel; se evaluarán en la fase del agente verificando su compatibilidad vigente con Laravel 13 antes de adoptarlos — no se asume de antemano.)

---

## 15. Arquitectura del agente

`ProjectDecisionAgent` (en `app/AI/Agents/`) orquesta:

1. Recibe la pregunta del usuario + su contexto de permisos (rol, proyectos accesibles).
2. Decide qué **Tools** internas necesita (no llama directamente a Eloquent).
3. Si la pregunta requiere contexto documental, invoca `SearchKnowledgeBaseTool` (que internamente pasa por el `Retriever` con filtro de permisos).
4. Compone un contexto con: resultados de tools + fragmentos RAG recuperados.
5. Llama al LLM con ese contexto y una instrucción explícita de **no inventar datos** y citar de dónde sale cada afirmación.
6. Devuelve texto + metadata de trazabilidad (qué tools se usaron, qué documentos se citaron).

---

## 16. Tools del agente

| Tool | Devuelve |
|---|---|
| `GetProjectStatusTool` | estado, prioridad, fechas de un proyecto |
| `GetProjectRiskTool` | salida de `ProjectRiskService` |
| `GetDelayedProjectsTool` | lista de proyectos con tareas atrasadas |
| `GetEmployeeWorkloadTool` | carga de trabajo por empleado |
| `GetProjectTasksTool` | tareas de un proyecto (con filtros de estado) |
| `GetBlockedTasksTool` | tareas bloqueadas y motivo |
| `GetProjectProgressHistoryTool` | serie de `task_progress_updates` |
| `GetProjectMetricsTool` | salida de `ProjectMetricsService` |
| `GetProjectCommentsTool` | comentarios marcados como relevantes |
| `GetProjectMembersTool` | miembros activos del proyecto |
| `SearchKnowledgeBaseTool` | fragmentos RAG relevantes (ya filtrados por permisos) |

Cada Tool es una clase simple que llama a un Service/Model existente y devuelve un array/DTO — **cero lógica de negocio nueva** vive en las Tools, solo adaptan la capa 1-3 al formato que el agente puede consumir.

---

## 17. Flujo de una pregunta al agente

Ejemplo: *"¿Qué proyectos necesitan más atención?"*

```
1. Usuario envía pregunta (POST /ai/chat)
2. Se resuelve el usuario autenticado y su alcance de permisos
3. Agente identifica que necesita: proyectos activos + riesgo + retrasos + bloqueos
4. Invoca GetDelayedProjectsTool, GetProjectRiskTool (por proyecto activo), GetBlockedTasksTool
5. Si el agente detecta que el usuario pregunta "por qué", además invoca SearchKnowledgeBaseTool
   sobre el/los proyecto(s) más críticos (respetando permisos)
6. Se construye un contexto estructurado (JSON de datos + fragmentos RAG con su fuente)
7. Se llama al LLM con ese contexto + instrucción de no inventar datos
8. El LLM redacta: identificación de proyectos, motivos (citando los datos recibidos),
   recomendaciones
9. Se guarda el mensaje en ai_messages con: tools_used, retrieved_documents, metadata
10. Se responde al usuario con: texto + lista de "datos utilizados" + lista de "fuentes RAG"
```

---

## 18. Seguridad y autorización

- **Web**: Policies de Laravel por modelo (`ProjectPolicy`, `TaskPolicy`) + Gates para acciones transversales (ej. "ver dashboard ejecutivo").
- **Roles**: Administrador (todo), Gerente (lectura global + agente), Líder de Proyecto (gestión de sus proyectos asignados), Empleado (solo lo suyo). Se implementa con un paquete de roles/permisos ampliamente adoptado (ver sección 21), no con un sistema propio.
- **RAG — regla crítica**: el filtrado de permisos ocurre **en el `Retriever`, en el backend, antes o durante la búsqueda vectorial** — nunca se delega al prompt del LLM. En la práctica: `rag_documents.project_id` se cruza contra los proyectos a los que el usuario tiene acceso (según su rol y `project_members`) **antes** de calcular similitud; un chunk de un proyecto no autorizado ni siquiera entra al cálculo de similitud, y por tanto nunca puede aparecer citado.
- **API**: autenticación con Laravel Sanctum (tokens), mismas Policies reutilizadas desde los controladores API.

---

## 19. Flujo de actualización de una tarea

```
1. Empleado abre su tarea → cambia % de avance + agrega comentario
2. Form Request valida (0-100, no retroceder sin justificar si se decide esa regla, etc.)
3. Action (ej. RecordTaskProgressAction):
     - crea fila en task_progress_updates (previous %, new %, comment)
     - actualiza tasks.progress_percentage
     - si %=100 → marca tasks.status=completed, tasks.completed_at
4. Dispara evento TaskProgressUpdated
5. Listeners (async vía queue si el cálculo es costoso):
     - recalculan métricas del proyecto
     - recalculan riesgo del proyecto
     - el motor de decisiones reevalúa señales
6. Si cruza un umbral (ej. pasa a "critical") → se dispara una notificación
7. AuditLog registra "task_progress_updated" con el before/after relevante
```

---

## 20. Flujo de procesamiento de un documento RAG

```
1. Usuario sube archivo (PDF/DOCX/TXT), opcionalmente ligado a un project_id
2. Se guarda el archivo en storage + fila en rag_documents (status=pending)
3. Se despacha el Job ProcessRagDocument (cola) → respuesta HTTP inmediata al usuario
4. Worker:
     a. DocumentLoader extrae texto según mime_type
     b. Normalización de texto
     c. TextChunker divide en fragmentos con solape
     d. EmbeddingService genera el vector de cada chunk
     e. VectorStore persiste cada chunk (rag_chunks) con su project_id heredado (para el filtro de permisos)
     f. rag_documents.status = processed (o failed, con motivo, si algo falla)
5. AuditLog registra "rag_document_uploaded" / "rag_document_processed"
```

---

## 21. Tecnologías y paquetes recomendados

| Necesidad | Paquete/recurso | Justificación |
|---|---|---|
| Scaffolding de autenticación (Blade) | `laravel/breeze` (stack Blade) | Starter kit oficial de Laravel, sin Vue/React, genera login/registro/reset/perfil listos para adaptar. Verificar en instalación que su release soporte Laravel 13. |
| Roles y permisos | `spatie/laravel-permission` | Estándar de facto en el ecosistema Laravel, muy mantenido, evita reinventar tablas pivote de roles/permisos. Verificar matriz de compatibilidad con Laravel 13 al instalar. |
| Autenticación API | `laravel/sanctum` (incluido en Laravel) | Nativo, no requiere paquete externo adicional. |
| Extracción de texto PDF | `smalot/pdfparser` | Puro PHP, sin dependencias binarias externas, suficiente para extracción de texto (no OCR). |
| Extracción de texto DOCX | `phpoffice/phpword` | Estándar para leer `.docx` en PHP. |
| Colas | Driver `database` (nativo de Laravel) | No requiere Redis para el MVP; suficiente para el volumen esperado. Se documenta cómo migrar a Redis si hiciera falta. |
| Testing | Pest (opción del instalador oficial de Laravel) o PHPUnit | Ambos soportados oficialmente; se decide en la fase de instalación. |
| LLM / Embeddings | Cliente propio sobre el `Http` facade (sin SDK de terceros obligatorio) | Evita atarse a un paquete de orquestación LLM de vida incierta; toda la variabilidad de proveedor queda en `.env` y detrás de las interfaces de la sección 14. |

Antes de instalar cualquiera de estos, en la fase correspondiente se verificará la versión exacta compatible con Laravel 13 (no se asume de antemano).

---

## 22. Riesgos técnicos

- **Costo/latencia de embeddings y LLM**: mitigado con colas (no bloquean requests) y con `MySqlVectorStore` simple que evita infraestructura adicional en desarrollo.
- **Escalabilidad del vector store en MySQL**: aceptable para el volumen académico/MVP; documentado el punto exacto de reemplazo (`VectorStoreInterface`) si el volumen crece.
- **Dependencia de un proveedor de IA externo**: mitigado por las interfaces desacopladas (`EmbeddingServiceInterface`, `LlmClientInterface`) y variables de entorno (`AI_PROVIDER`, `AI_MODEL`).
- **Alucinación del LLM**: mitigado por diseño — el LLM solo redacta sobre datos ya calculados/recuperados (secciones 13, 15, 17) y se le instruye explícitamente a responder "No tengo suficiente información para determinarlo" cuando corresponda.
- **Fuga de información entre proyectos vía RAG**: mitigado por el filtrado de permisos en el `Retriever`, en backend (sección 18), nunca confiado al prompt.
- **Paquetes de terceros aún no actualizados a Laravel 13** al momento de instalar: se verificará compatibilidad exacta en cada fase antes de requerirlos (secciones 21 y 55 del prompt original).

---

## 23. Decisiones arquitectónicas importantes

1. **Notificaciones nativas de Laravel en vez de tabla `alerts` propia**: el sistema de `Notification`/`notifications` (morph) de Laravel ya cubre "ver", "marcar como leída", relación con un recurso y fecha. Crear una tabla `alerts` duplicaría esta funcionalidad sin aportar nada nuevo.
2. **Sin Repository Pattern**: justificado en la sección 6 — Eloquent ya es la abstracción, y no hay plan de cambiar de ORM/motor de datos empresarial.
3. **Sin DTOs formales generalizados**: Form Requests + colecciones Eloquent tipadas cubren las necesidades de este alcance; se evalúa introducir un DTO puntual solo si un caso concreto lo justifica (ej. el payload que viaja al LLM, que si conviene puede tener su propia clase simple de "contexto").
4. **Snapshots de métricas mínimos, no exhaustivos**: se recalculan al vuelo por defecto; solo se persiste snapshot cuando aporta valor real (gráficas de tendencia, ver sección 11).
5. **RAG y motor de decisiones desacoplados a propósito**: el motor de decisiones nunca depende del RAG para calcular señales — así el sistema funciona (y es testeable) incluso si el RAG está caído o vacío.
6. **Multi-tenancy no implementado, pero previsto**: no se agrega `company_id` en el MVP (no hace falta con una sola empresa), pero el diseño de servicios (sin estado global, todo parametrizado por `project_id`/`employee_id`) permite añadirlo después como una columna adicional + un scope global, sin rediseñar servicios.

---

## 24. Plan de implementación por fases

Se mantienen las 37 fases propuestas en el prompt original, agrupadas aquí solo para visibilidad (el orden y contenido exacto de cada fase no cambia):

**Fundación (2-7)**: estructura de carpetas → base de datos/ERD → instalación de Laravel 13 → MySQL → autenticación → roles y permisos.

**Dominio empresarial (8-13)**: empleados → proyectos → miembros de proyecto → tareas → dependencias → seguimiento de avances.

**Inteligencia determinística (14-18)**: métricas → riesgo → motor de decisiones → dashboard → alertas.

**RAG (19-26)**: arquitectura RAG → carga de documentos → procesamiento → chunking → embeddings → vector store → retriever → context builder.

**Agente (27-30)**: agente IA → tools → chat IA → integración del motor de decisiones con el agente.

**Endurecimiento (31-37)**: seguridad del RAG → auditoría → API → testing → documentación → optimización → preparación para producción.

Cada fase se ejecutará una por una, con el detalle completo (qué, por qué, archivos nuevos/modificados, comandos, cómo probar, errores comunes) antes de avanzar a la siguiente, y **sin avanzar automáticamente**: se espera confirmación explícita ("CONTINUAR") después de cada una.

---

## Cierre de la Fase 1

Esta propuesta cubre arquitectura, estructura de carpetas, base de datos, motor de métricas/riesgo/decisiones, RAG, agente y seguridad — sin una sola línea de código Laravel, migración o comando de instalación, tal como se solicitó.

**Quedo a la espera de tu confirmación ("CONTINUAR") o de los cambios que quieras hacer antes de avanzar a la Fase 2.**
