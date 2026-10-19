# Accesos por rol y presentación de listados

| Función | Trabajador | Líder de proyecto | Gerente | Administrador |
| --- | --- | --- | --- | --- |
| Inicio | Asistente | Dashboard de sus proyectos y áreas | Dashboard de gerencia | Dashboard general |
| Proyectos | Sin módulo general | Consulta y gestión según alcance de sus áreas y proyectos | Acceso operativo global y creación | Acceso global y creación |
| Actividades | Sus propias actividades y evidencia de su trabajo | Actividades de su alcance | Acceso operativo global | Acceso global |
| Trabajadores | Su expediente | Gestión de trabajadores de sus áreas | Gestión operativa de trabajadores | Gestión global |
| Áreas de trabajo | Solo la asignada | Áreas asignadas y bajo su liderazgo | Todas | Todas |
| Documentos y asistente | Consulta de fuentes generales y autorizadas | Según sus áreas/proyectos | Consulta global | Consulta global |
| Clientes | Sin acceso | Sin módulo general | Consulta, creación y edición | Gestión global |
| Contrato | Consulta del suyo | Consulta de trabajadores de su alcance | Consulta autorizada | Consulta global |
| TDR | Lectura de su área | Lectura de sus áreas | Administración | Administración |
| Faltas del manual | Lectura aplicable | Edición de faltas específicas de sus áreas; no modifica reglas generales compartidas | Administración | Administración |
| Sanciones aplicadas | Lectura y descarga de las suyas | Registro y edición para trabajadores de su alcance | Registro y edición autorizada | Gestión global |
| Contraseñas de trabajadores | No puede modificarlas ni recuperarlas por correo | Asignación en su alcance | Asignación a trabajadores | Asignación y administración |
| Roles y configuración crítica | Sin acceso | Sin acceso | Sin administración de roles | Administración |

Los controles se aplican en el servidor, las consultas, los enlaces y los formularios. Ocultar una opción no concede acceso mediante una dirección escrita manualmente. El alcance de liderazgo se obtiene de las áreas y proyectos asignados; el trabajador sin un área o especialidad válida no obtiene acceso automático a otras áreas.

## Contraseñas permanentes

Las cuentas nuevas y los cambios realizados por un responsable autorizado usan contraseñas permanentes. No se obliga al trabajador a crear otra al ingresar. Se mantiene el requisito de longitud y complejidad y se invalidan sesiones anteriores cuando cambia la contraseña. El trabajador no puede cambiarla desde el perfil ni usando un enlace de recuperación.

Para asignar una contraseña, abre el trabajador y selecciona **Asignar contraseña permanente**. El responsable confirma su propia contraseña y escribe la nueva dos veces. La administración completa de roles permanece en Usuarios y accesos, para el administrador. No se modificaron automáticamente las contraseñas existentes.

## Expediente y sanciones

**Mi expediente** permite consultar el convenio, el TDR y las sanciones registradas. Las actas nuevas quedan guardadas además de generar su PDF. Las actas que se generaron antes de esta mejora no tenían un registro estructurado; no se reconstruyen automáticamente. Cada edición de una sanción deja un registro de cambios.

El manual de faltas y sanciones contiene reglas compartidas. Una sanción aplicada pertenece a una persona concreta. Son registros distintos y no deben contarse como si fueran lo mismo.

## Asistente

El administrador y el gerente pueden pedir `Créame un proyecto llamado campaña de marketing`. El asistente muestra un borrador editable con código, tipo, cliente, responsable, áreas y fechas. Al guardar se usa la validación habitual del módulo y el responsable queda incorporado al equipo del proyecto. También pueden preparar actividades; el líder lo hace dentro de su alcance.

Se añadieron consultas directas a clientes, usuarios, áreas, TDR, contrato propio, faltas, sanciones, notificaciones y auditoría, además de empleados, actividades, proyectos y documentos ya disponibles. El estado técnico y los respaldos se consultan solo como administrador. Cada consulta mantiene el alcance del rol. No se ejecutan cambios arbitrarios en otros módulos por medio del chat.

## Paginación y móvil

- En computadora: cinco registros por página y controles numerados.
- En móvil o tableta de menos de 1024 px: dos registros por página, representados como tarjetas.
- Los filtros permanecen en la URL al avanzar. En móvil se pliegan inicialmente si no hay filtros aplicados.
- Las actividades tienen una paginación común para el conjunto de tarjetas; no cinco tarjetas adicionales por cada columna del tablero.
- Los totales y métricas consideran todos los registros autorizados, aunque solo se muestre una página.

El navegador selecciona el tamaño de página; el servidor solo acepta dos o cinco registros. Las tablas auxiliares tienen su propia navegación cuando es necesaria. Las vistas se comprobaron con registros ficticios, a 390 px y 1440 px, sin desbordamiento horizontal.
