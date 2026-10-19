# Diseño del espacio de trabajo

## Navegación y búsqueda

La barra superior permite buscar proyectos, actividades y empleados por nombre o código. Se abre con el botón Buscar o Ctrl/Cmd K. La búsqueda exige al menos dos caracteres y aplica controles de acceso en el servidor. Devuelve hasta ocho coincidencias por tipo.

El inicio conserva las variantes por rol y añade un resumen de la jornada, accesos a actividades y alertas y acciones según los permisos. El panel ejecutivo prioriza cuatro indicadores y los proyectos que requieren atención; los indicadores adicionales y gráficos se pueden desplegar.

## Actividades

- Presentación en tablero o lista, con la última presentación conservada en este navegador.
- Filtros rápidos: mis actividades, vencen hoy, atrasadas y por revisar.
- Filtros avanzados desplegables y etiquetas para quitar filtros individuales.
- Una vista favorita por usuario y navegador. Guardar vista reemplaza la favorita anterior; Abrir favorita restaura sus filtros y presentación. No se comparte entre dispositivos.
- Panel lateral con responsable, estado, descripción, avance y fecha. Permite cambios de estado según los permisos existentes. La finalización mantiene el flujo de revisión de evidencias.
- Confirmación al guardar y opción de reintentar un cambio fallido.
- Las actividades que vencen hoy se distinguen de las que vencieron antes de hoy. Los registros finalizados muestran la fecha sin describirlos como atrasados.

## Proyectos

Pestañas: Resumen, Actividades, Equipo, Documentos e Historial. La pestaña se conserva en la dirección de la página para compartir o recargar esa vista. Las actividades visibles respetan sus permisos; los documentos mantienen el alcance RAG. El historial presenta los últimos treinta eventos registrados directamente sobre el proyecto.

## Formularios y documentos

Los formularios modales muestran un resumen de validación y un estado Guardando al enviarse. El formulario de actividad agrupa contexto, plazos y tareas y restaura sus campos después de la validación. Los archivos seleccionados muestran nombre y tamaño; las imágenes raster compatibles incluyen una vista previa local.

El asistente ofrece preguntas sugeridas que se pueden editar antes de enviarlas. Ante un error se conserva la pregunta. Las fuentes citadas abren una página con el texto extraído del documento, con comprobación de permisos. Esta página no sustituye al archivo original ni proporciona un visor PDF.

## Archivos principales

- [Estilos compartidos](../resources/css/app.css)
- [Interacciones compartidas](../resources/js/modules/workspace.js)
- [Barra y búsqueda](../resources/views/components/ui/workspace-toolbar.blade.php)
- [Resumen de jornada](../resources/views/components/ui/day-overview.blade.php)
- [Tablero](../resources/views/tasks/index.blade.php)
- [Lista y panel lateral](../resources/views/tasks/partials/workspace.blade.php)
- [Pestañas del proyecto](../resources/views/projects/partials/workspace.blade.php)
- [Consulta de fuentes](../resources/views/rag/source.blade.php)
- [Pruebas de permisos y experiencia](../tests/Feature/Access/WorkspaceExperienceTest.php)

## Verificación

Se comprobaron la compilación de recursos y vistas y las interacciones en una base SQLite local con datos ficticios. Se revisaron los tamaños de escritorio (1440 px) y móvil (390 px), la búsqueda, las pestañas, los filtros, el guardado de una vista favorita, la consulta de fuentes y el cambio de estado desde el panel lateral.

La suite final ejecutó 88 pruebas: 74 pasaron y 14 encontraron el mismo problema de datos de prueba existente antes del rediseño: falta `miembros_proyecto.rol_en_proyecto`. Las ocho nuevas pruebas de búsqueda, permisos, fuentes y fechas pasaron. Las llamadas a proveedores externos de IA no se ejecutaron durante la comprobación visual.
