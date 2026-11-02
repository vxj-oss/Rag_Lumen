# Diseño vigente: papel editorial y agente conversacional

Referencia general: DESIGN.md (Steep). Referencia del asistente: capturas Solvinger proporcionadas por el usuario. Los temas anteriores se sustituyen por una única hoja compartida.

## Sistema

Fondo blanco y gris muy claro, títulos Georgia regular como sustituto serif de Signifier, interfaz Plus Jakarta Sans existente, botones negros en forma de píldora, tarjetas de 24 px y bordes discretos. El durazno #fbe1d1 se reserva para el bloque de atención de la jornada; el marrón #5d2a1a para ese bloque y trazos de los gráficos. Los estados mantienen etiquetas y colores para facilitar su interpretación.

## Login

Formulario centrado sin texto promocional lateral. Fondo de figuras de papel que se desplazan lentamente y responden suavemente al ratón. Se conservan pausa, preferencia guardada y movimiento reducido, sin añadir librerías, imágenes o vídeos.

## Agente

Panel grafito independiente del tema claro del sistema, encabezado discreto, bienvenida con símbolo geométrico, sugerencias en tarjetas, respuestas sin grandes cajas y mensajes del usuario en burbujas. El compositor separa el texto de la fila de micrófono y envío azul. Permite copiar y escuchar respuestas, consultar fuentes y abrir el historial.

Los borradores de proyectos conservan su editor amplio y los controles de creación existentes. El editor se muestra en una ventana clara fuera del panel oscuro. Se mantienen dictado, gestión del historial y permisos existentes.

## Conservación

No se cambian datos ni permisos, no se restauran exportaciones CSV ni los desplegables vacíos. Se conservan dos registros por página en móvil y cinco en escritorio, el menú plegable y el desplazamiento según el contenido.

## Distribución compacta

Se elimina la franja de títulos y subtítulos de los módulos. El nombre de la página se conserva como encabezado accesible sin ocupar espacio visual. Las acciones de cada módulo se incorporan a la barra superior mediante workspace-page-actions. En fichas individuales se conserva el nombre del registro como contexto breve. No se duplican los accesos de Actividades y Alertas de la barra lateral. El resumen de jornada y las cabeceras de listas se compactan, conservando las métricas y sus etiquetas.

## Apariencia y agente movible

El botón de sol/luna se comparte entre el login y la barra superior: un clic alterna entre claro y oscuro, sin desplegables. Si todavía no hay una elección guardada, se toma el tema del dispositivo. La preferencia se guarda en workspace.theme, se aplica antes de cargar los estilos y se sincroniza entre pestañas. El agente sigue la apariencia elegida; los campos y el compositor usan los mismos tokens para evitar fondos blancos sobre el tema oscuro.

El robot flotante y el asa de su encabezado permiten arrastrar con ratón o tacto, o mover con las flechas del teclado. Su posición se conserva por usuario, se limita al tamaño disponible y el panel se abre sobre o bajo el robot según el espacio. Restablecer posición devuelve el robot a la esquina. El modo a pantalla completa mantiene su disposición de trabajo. Los movimientos decorativos respetan la preferencia de movimiento reducido.

Desde la vista completa, el botón Ventana permite usar el mismo agente como panel flotante. Ampliar devuelve la conversación a pantalla completa, manteniendo mensajes, pregunta escrita y los campos editados del proyecto.

La legibilidad se revisa con combinaciones principales de texto y fondo de al menos 4.5:1. Las etiquetas, leyendas y trazos de los gráficos cambian inmediatamente con el tema, sin alterar sus datos. Se refuerzan los textos secundarios y los mensajes de confirmación.
