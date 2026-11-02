# Rendimiento y disposición de las áreas

- Las pestañas se distribuyen en varias filas cuando falta espacio y conservan margen para sus bordes y el foco del teclado.
- Los listados del área paginan en la base de datos: cinco registros en escritorio y dos cuando se solicita la vista móvil. Los indicadores y resúmenes de proyectos se calculan sobre todos los registros autorizados, no solo sobre la página visible.
- Las próximas entregas cargan como máximo cinco actividades. Los cálculos por colaborador se agrupan en la base de datos.
- Los cálculos de riesgo y decisiones comparten las tareas y cargas de trabajo durante una solicitud. Al guardar o eliminar una tarea se descartan esos resultados para recalcularlos.
- Los gráficos se descargan cuando su sección se acerca a la pantalla. El paquete inicial de gráficos mide aproximadamente 1,7 KB; el paquete diferido, 210 KB antes de compresión.
- La interfaz utiliza fuentes del sistema para evitar esperas por un proveedor externo. El fondo animado del acceso se conserva.
- La consulta periódica del contador de alertas se pausa si la pestaña está oculta y evita solicitudes superpuestas.

## Comprobación reproducible

`WorkspacePerformanceTest` utiliza una base temporal con 25 colaboradores y 120 actividades. Comprueba los totales y limita los modelos cargados, sin establecer un umbral de tiempo dependiente del equipo.

En una comparación local de tres solicitudes por versión, la mediana pasó de 106,4 ms a 43,3 ms. Las actividades cargadas pasaron de 120 a 10 y los colaboradores de 26 a 7. Esta medición corresponde a la respuesta del servidor con datos de prueba; no representa el tiempo total del navegador ni garantiza el mismo resultado en otros equipos. La paginación y las agregaciones aumentan el número de consultas, pero reducen los registros transferidos y procesados.

Los estilos y scripts deben recompilarse mediante `npm run build` después de modificarlos.

## Presentación móvil

Los listados usan dos registros por página hasta 1023 px y cinco en escritorio. La preferencia `workspace_list_size` se guarda como cookie de presentación, validada a los valores 2 y 5; no contiene datos de sesión ni permisos. La primera solicitud también puede detectar un dispositivo móvil por sus cabeceras. El parámetro explícito `per_page` tiene prioridad.

Las tablas sin una versión móvil propia se convierten en tarjetas con etiquetas tomadas de sus encabezados. Las tablas locales de los dashboards, equipos y detalles tienen paginación independiente. Los listados paginados por el servidor conservan sus filtros y los espacios de área mantienen sus números de página independientes al cambiar el ancho. Las secciones de actividades, documentos e historial del proyecto también paginan y conservan su pestaña.

Los formularios de edición de empleados, proyectos, clientes y áreas se renderizan fuera de las tablas y de los contenedores exclusivos de escritorio para poder abrirse en móvil.
