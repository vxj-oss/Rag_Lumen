# Operación y recuperación

Consulta la [matriz actual de permisos](permisos-roles.md) para los cambios de roles, contraseñas permanentes y expediente personal.

## Cuentas y permisos

El administrador gestiona las cuentas desde **Equipo → Usuarios**. Puede vincular una cuenta con un empleado, asignar su rol, desactivarla y restablecer su contraseña confirmando su propia contraseña. Las contraseñas nuevas requieren al menos 12 caracteres, mayúsculas, minúsculas y números. Las contraseñas asignadas son permanentes; no se solicita al trabajador crear una nueva al ingresar. Los cambios de contraseña, rol o acceso invalidan las sesiones anteriores. El último administrador activo está protegido.

El trabajador no cambia su propia contraseña: la asigna el administrador, gerente o líder autorizado desde su ficha. Nunca se muestran contraseñas existentes. Antes de archivar un empleado con acceso, se debe desactivar su cuenta.

| Rol | Alcance principal |
| --- | --- |
| Administrador | Administración de usuarios, configuración, respaldos y restauración de registros archivados. |
| Gerente | Gestión operativa global, creación de proyectos y actividades; asignación de contraseñas a trabajadores. |
| Líder de proyecto | Proyectos y áreas a su cargo, trabajadores de ese alcance, actividades, sanciones y contraseñas de sus trabajadores. |
| Empleado | Asistente, documentos autorizados, área asignada, actividades propias y expediente de consulta. |

Las políticas del servidor comprueban cada operación, aunque se intente acceder directamente a una dirección. El asistente usa el mismo alcance de acceso. Permite preparar actividades y guardarlas tras revisar un formulario; administradores, gerentes y líderes autorizados pueden hacerlo. El administrador y el gerente también pueden crear proyectos desde un borrador. No ejecuta otras modificaciones ni eliminaciones por conversación.

## Asistente y robot

El robot está disponible en las pantallas de trabajo y comparte conversaciones con la página del asistente. Permite consultas como «¿Cuántos empleados activos hay?», «¿Cuáles trabajan en marketing?» o consultas de proyectos, actividades y documentos. Conserva contexto y solicita aclaraciones cuando hay varios nombres posibles. Los listados muestran hasta 20 registros; el conteo corresponde a toda la consulta autorizada.

Los datos de la base se consultan mediante operaciones limitadas y validadas, sin ejecutar SQL generado por IA. Las respuestas documentales incluyen fuentes accesibles. La recuperación documental usa búsqueda textual y no requiere un servicio pagado de embeddings. La comprensión de preguntas complejas depende de la disponibilidad del modelo; las consultas locales compatibles siguen funcionando sin él.

Las consultas sobre cantidad o listado de documentos usan directamente los registros de la base, incluidos los pendientes de procesamiento, y excluyen los eliminados y los que no están dentro de tus permisos. Los fragmentos y referencias de un mismo archivo no aumentan el total. Los filtros por estado y el proyecto seleccionado pueden reducir el conteo. Las respuestas antiguas guardadas no se recalculan: vuelve a preguntar para obtener el resultado actual.

### Groq gratuito

Configura `GROQ_API_KEY` de forma privada. Los modelos quedan disponibles automáticamente, sin confirmaciones adicionales en el panel. Mantén tu organización de Groq en Free para conservar el uso gratuito.

Se aplica una lista permitida de modelos, un presupuesto local diario compartido y espera cuando un modelo alcanza límites. Si falla uno, se intenta otro permitido; cambiar de modelo no elimina los límites compartidos de tu cuenta. Si todos están agotados, se informa al usuario. No hay contratación ni ampliación automática. Los modelos y límites pueden cambiar: consultar [documentación de Groq](https://console.groq.com/docs/rate-limits).

Las claves nunca se muestran en el panel ni se incluyen en respaldos.

### Voz

El micrófono requiere HTTPS o localhost y permiso del navegador. El dictado usa SpeechRecognition cuando está disponible y muestra palabras provisionales mientras hablas. El navegador puede utilizar su propio servicio de reconocimiento. En Opciones se ofrece grabación alternativa: envía hasta 30 segundos de audio a Groq al detenerla, sin conservar la grabación en el servidor. El texto se añade al borrador y puede revisarse antes de preguntar. La lectura de respuestas usa las voces disponibles en el dispositivo. No hay escucha permanente.

El robot ocupa 52 píxeles cerrado. El panel flotante mide como máximo 384 × 528 píxeles y se adapta a la pantalla; historial, lectura automática y cambio de posición están en Opciones.

## Alertas y procesos

El dashboard y la sección «Situaciones que requieren atención» de Alertas utilizan el mismo cálculo actualizado por rol. Los administradores ven proyectos con señales de atención; los gerentes, proyectos de su alcance con riesgo alto o crítico; los líderes, actividades bloqueadas de sus proyectos; los empleados, próximas entregas propias. Los proyectos completados o cancelados no generan señales operativas. Estas situaciones desaparecen al resolver su causa, no al marcarlas como leídas.

Las «Notificaciones guardadas» son un historial independiente con contador de mensajes sin leer. Dependen de eventos, preferencias y procesos programados. Leerlas no resuelve un atraso o bloqueo. Por eso sus cantidades pueden diferir de las situaciones activas, que ahora se muestran en la misma pantalla.

## Crear actividades y analizar proyectos desde el asistente

Ejemplo: `Crea una actividad "Revisar portal" con prioridad alta. Tareas: Revisar diseño; Validar formulario`. El asistente muestra un borrador editable. Selecciona proyecto, miembro activo, fechas y tareas; después pulsa «Crear actividad». Se usa el mismo flujo del formulario habitual, con historial de estado y notificaciones. Los borradores de esta primera versión permanecen en la conversación abierta; no se recuperan como formularios tras recargar. La actividad guardada sí permanece en el sistema.

Para consultar la cartera escribe `Analiza todos los proyectos`. Se calculan todos los proyectos de tu alcance con las métricas actuales del sistema, sus riesgos, fechas, responsables y recomendaciones. Se detallan los 20 de mayor riesgo para mantener la respuesta manejable. Este análisis consulta la base de datos y no depende de haber subido documentos al RAG.

Las preferencias del perfil permiten activar o desactivar notificaciones. Se notifican asignaciones y cambios del flujo de revisión; las alertas periódicas requieren que funcione el programador. El correo necesita SMTP configurado.

En desarrollo, `composer run dev` inicia el programador y la cola junto con la aplicación. En alojamiento, configurar el programador de Laravel cada minuto (`php artisan schedule:run`) y un trabajador de cola persistente (`php artisan queue:work`). El panel de sistema muestra el último pulso, trabajos pendientes y documentos fallidos para reintentarlos. La configuración del servidor de alojamiento debe realizarse al desplegar.

## Respaldos y recuperación

Crear y verificar un respaldo: `php artisan workspace:backup --verify`. También se puede crear desde el panel del administrador. Los archivos cifrados se guardan en `storage/app/private/backups`; las descargas requieren la contraseña del administrador. Hay una ejecución diaria prevista a las 02:00, hora de Lima, siempre que el programador esté activo.

Conserva una copia externa del respaldo y del `APP_KEY` por separado y con acceso restringido. Sin la clave original no puede recuperarse el archivo. No cambies ni regeneres esa clave. El respaldo requiere la misma estructura de base de datos; un respaldo anterior a una migración exige recuperar también la versión compatible del código y del esquema.

1. Verificar sin modificar datos: `php artisan workspace:restore NOMBRE.zip --verify-only`.
2. Preparar la versión y esquema compatibles, conservar la clave original y activar mantenimiento con `php artisan down`.
3. Restaurar con `php artisan workspace:restore NOMBRE.zip --force`. Se genera una copia de seguridad previa y se invalidan sesiones.
4. Comprobar datos y archivos antes de volver a habilitar el servicio con `php artisan up`.

El respaldo incluye datos y archivos de los discos de trabajo; excluye claves, sesiones, colas y otros respaldos. El límite actual es 128 MB de contenido total y 32 MB por archivo. Para volúmenes mayores, adaptar la estrategia antes de depender de ella. Revisar el espacio disponible y conservar las copias necesarias: no se borran respaldos automáticamente.

Los empleados, proyectos y actividades archivados se recuperan desde **Estado del sistema → Archivo**. Una actividad necesita que su proyecto y actividad superior estén disponibles antes de recuperarla.

## Comprobaciones y límites

Las pruebas automatizadas cubren permisos, cuentas, consultas y contexto del asistente, límites y alternativas de Groq con respuestas simuladas, flujo de actividades y recuperación de respaldos. Las pruebas de interfaz se ejecutaron con datos ficticios aislados. No se cambiaron contraseñas ni roles de usuarios reales. No se ha realizado una llamada real a Groq ni una grabación física de micrófono durante estas comprobaciones; confirmar esos dos puntos en el entorno de despliegue.
