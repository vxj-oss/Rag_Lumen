# Plan de evolución del asistente y RAG

## Base implementada

Consultas autorizadas a registros, inventario real de documentos, recuperación textual con fuentes, conversación y voz, borradores editables para crear actividades y proyectos, análisis de proyectos con las métricas del sistema y alternativas de modelos limitadas al catálogo permitido. Las situaciones actuales de atención y el historial de notificaciones se muestran por separado.

## 1. Acciones fiables y continuidad

- Guardar borradores en el servidor, recuperarlos al volver y registrar el resultado de creación en la conversación.
- Asignar un identificador persistente a cada acción para impedir duplicados incluso con cortes de red, recargas o varios dispositivos.
- Interpretar fechas, nombres y tareas complejas con una estructura validada; preguntar cuando haya varias coincidencias. Nunca inventar responsables ni fechas.
- Añadir reprogramación y reasignación con vista previa, historial de cambios y permisos vigentes. Mantener eliminaciones y credenciales fuera del asistente.

Criterio de aceptación: reintentar una misma acción no duplica actividades; una pérdida de permisos impide guardar un borrador antiguo.

## 2. Calidad y trazabilidad del conocimiento

- Citas con documento, página o sección y fragmento exacto; identificar versiones y fecha de carga.
- Detección de archivos duplicados por contenido y sustitución controlada de versiones.
- OCR para documentos escaneados y extracción de tablas conservando encabezados.
- Vista de calidad de extracción: texto vacío, páginas ilegibles y documentos desactualizados.

Criterio de aceptación: cada afirmación documental relevante puede contrastarse con un pasaje; se informa cuando una fuente no basta o contradice otra.

## 3. Mejor recuperación sin servicios de pago obligatorios

- Comparar la búsqueda textual actual con búsqueda por relevancia y sinónimos del negocio.
- Evaluar embeddings locales y combinación de búsqueda textual y semántica, midiendo resultados y consumo del equipo antes de adoptarlos.
- Limitar contexto por relevancia y evitar repetir fragmentos del mismo documento.
- Reaplicar permisos en recuperación, citas e historial cuando cambie la pertenencia de un usuario.

Criterio de aceptación: mejora medible frente a preguntas reales de la organización, sin aumentar respuestas inventadas ni exponer datos ajenos.

## 4. Análisis que combina proyectos y documentos

- Comparar entregables y plazos de contratos con actividades y fechas registradas, señalando diferencias y fuentes.
- Resúmenes por proyecto, área y período; tendencias de riesgo, carga y cumplimiento con fecha de corte.
- Sugerir un desglose de actividades desde un documento para que el responsable lo revise antes de crearlo.
- Exportar informes con métricas verificadas y referencias. Las recomendaciones deben distinguir hechos, cálculos y propuestas.

Criterio de aceptación: totales coinciden con los módulos del sistema y cada diferencia entre contrato y ejecución tiene evidencia consultable.

## 5. Alertas útiles y operación

- Configurar responsables, umbrales y frecuencia por tipo de alerta; agrupar avisos repetidos.
- Mostrar causa, fecha de detección, última actualización y resolución de cada situación.
- Añadir indicadores de cola, disponibilidad del modelo, tiempos y consumo observado; cola de reintentos limitada.
- Recordatorios dentro del sistema y resúmenes periódicos según preferencias, sin enviar correos no autorizados.

Criterio de aceptación: un mismo problema no genera avisos repetidos sin cambios y las situaciones activas siempre coinciden con los datos actuales.

## 6. Evaluación antes de ampliar funciones

- Banco de preguntas con respuestas esperadas: conteos, permisos, nombres ambiguos, documentos repetidos, preguntas sin respuesta y contexto cambiado.
- Pruebas de fuentes, instrucciones maliciosas dentro de documentos y cambios de acceso durante una conversación.
- Valorar «respuesta útil / incorrecta» y revisar errores sin registrar claves ni audio privado.
- Medir exactitud, cobertura de fuentes, latencia y consumo. Ajustar primero los errores frecuentes.

Orden recomendado: completar acciones fiables y evaluación, mejorar las fuentes y recuperación, y después ampliar análisis y automatización. Mantener el uso gratuito configurado; no contratar servicios externos automáticamente.
