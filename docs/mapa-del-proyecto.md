# Mapa del proyecto

## ¿Qué quieres cambiar?

| Necesidad | Ubicación |
|---|---|
| Diseño e interacciones del espacio de trabajo | [Guía del diseño](diseno-interactivo.md) |
| Búsqueda global y permisos de resultados | [SearchController](../app/Http/Controllers/Web/Search/SearchController.php) |
| Pantallas | [resources/views](../resources/views/) |
| Comportamiento de las pantallas | [Controladores Web](../app/Http/Controllers/Web/) |
| Acceso, inicio de sesión y contraseñas | [Controladores Auth](../app/Http/Controllers/Auth/) |
| Perfil de usuario | [ProfileController](../app/Http/Controllers/ProfileController.php) |
| Campos obligatorios y validación | [Requests](../app/Http/Requests/) |
| Relaciones y acceso a datos | [Models](../app/Models/) |
| Permisos sobre los registros | [Policies](../app/Policies/) |
| Cálculos de métricas, riesgo y decisiones | [Services](../app/Services/) |
| Envío y revisión de evidencias | [Actions](../app/Actions/) |
| Procesamiento y consulta de documentos | [RAG](../app/RAG/) |
| Direcciones y rutas de las páginas | [web.php](../routes/web.php) |
| Estructura de la base de datos | [migrations](../database/migrations/) |
| Datos iniciales y demostración | [seeders](../database/seeders/) |
| Pruebas de permisos y tareas | [tests/Feature/Access](../tests/Feature/Access/) |
| Configuración del sistema | [config](../config/) |

## Acceso por módulo

| Módulo | Controladores | Pantallas | Validación |
|---|---|---|---|
| Tareas | [Tasks](../app/Http/Controllers/Web/Tasks/) | [tasks](../resources/views/tasks/) | [Task](../app/Http/Requests/Task/) y [TaskEvidence](../app/Http/Requests/TaskEvidence/) |
| Proyectos | [Projects](../app/Http/Controllers/Web/Projects/) | [projects](../resources/views/projects/) | [Project](../app/Http/Requests/Project/) y [ProjectMember](../app/Http/Requests/ProjectMember/) |
| Empleados | [Employees](../app/Http/Controllers/Web/Employees/) | [employees](../resources/views/employees/) | [Employee](../app/Http/Requests/Employee/) |
| Clientes | [Clients](../app/Http/Controllers/Web/Clients/) | [clients](../resources/views/clients/) | [Client](../app/Http/Requests/Client/) |
| Áreas | [Areas](../app/Http/Controllers/Web/Areas/) | [areas](../resources/views/areas/) | [Area](../app/Http/Requests/Area/) |
| Normativa | [Normativa](../app/Http/Controllers/Web/Normativa/) | [normativa](../resources/views/normativa/) | En el controlador |
| Paneles | [Dashboard](../app/Http/Controllers/Web/Dashboard/) | [dashboard](../resources/views/dashboard/) | — |
| Documentos y chat RAG | [Rag](../app/Http/Controllers/Web/Rag/) | [rag](../resources/views/rag/) y [rag-chat](../resources/views/rag-chat/) | [RagDocument](../app/Http/Requests/RagDocument/) y [RagQuery](../app/Http/Requests/RagQuery/) |
| Espacios de trabajo | [Workspaces](../app/Http/Controllers/Web/Workspaces/) | [workspaces](../resources/views/workspaces/) | — |
| Alertas | [Alerts](../app/Http/Controllers/Web/Alerts/) | [alerts](../resources/views/alerts/) | — |
| Registro de actividad | [Activity](../app/Http/Controllers/Web/Activity/) | [activity](../resources/views/activity/) | — |
| Configuración de firma | [Settings](../app/Http/Controllers/Web/Settings/) | [settings](../resources/views/settings/) | En el controlador |
| Política de seguridad | [Legal](../app/Http/Controllers/Web/Legal/) | [legal](../resources/views/legal/) | — |

## Convenciones para encontrar archivos

- `index.blade.php`: listado o entrada del módulo.
- `show.blade.php`: detalle de un registro.
- `partials/`: fragmentos de una pantalla, como formularios.
- `resources/views/components/`: elementos reutilizables entre módulos.
- `resources/views/layouts/`: estructura general de las pantallas.
- `resources/views/reports/`: plantillas de reportes y documentos exportados.
- `resources/js/modules/`: JavaScript por módulo; actualmente contiene `dashboard.js`.
- Los controladores se agrupan con el nombre plural del módulo; las carpetas de validación existentes conservan el singular del modelo, como `Tasks` y `Task`.

## Archivos de trabajo y datos

`vendor/` y `node_modules/` contienen dependencias. `storage/framework/` contiene archivos de ejecución y `storage/logs/` los registros. Se ocultan del explorador de VS Code para facilitar la navegación.

`storage/app/private/` contiene archivos reales de la aplicación, como firmas, evidencias y documentos RAG. Conserva estos archivos y respáldalos junto con la base de datos.

Los documentos antiguos están en `docs/historial/fases/`; sus rutas describen la estructura de aquel momento.
