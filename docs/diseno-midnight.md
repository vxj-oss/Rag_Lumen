# Diseño vigente del espacio de trabajo

Referencia vigente: DESIGN.md (OpenSea), adaptada por petición del usuario a una combinación de navegación oscura y contenido claro. Sustituye los estilos anteriores de Authkit y ORYZO.

## Identidad

- Navegación grafito #141415 y #26272d; fondo principal claro #f4f6f8 y tarjetas blancas.
- Texto principal #20252d y secundario #626d7b.
- Azul hielo #83c3ff para bordes, indicadores y focos. Los enlaces sobre fondo claro usan un azul más oscuro para facilitar la lectura.
- Botones de acción delineados, radios de 4 px en controles y 8 px en tarjetas.
- Números alineados con fuente monoespaciada; estados con etiquetas y colores semánticos.

## Implementación

resources/css/workspace-theme.css define el único tema compartido para body.workspace-theme. Las nuevas interfaces utilizan workspace-card, workspace-primary, workspace-button, workspace-list-heading y workspace-record-card. Las exportaciones PDF conservan sus plantillas.

El login mantiene un formulario centrado, campos con borde visible, un ejemplo de correo y un botón de acceso explícito. El fondo combina planos claros y grafito con mallas geométricas SVG que giran lentamente. No contiene textos promocionales laterales.

resources/js/modules/auth-scene.js permite pausar el fondo, recuerda esa preferencia y respeta movimiento reducido. La decoración está oculta para lectores de pantalla y no captura clics. No hay imágenes, vídeos ni dependencias nuevas.

## Comportamiento conservado

Se mantienen permisos, datos, contraseñas, paginación de dos registros en móvil y cinco en escritorio y el menú plegable. No se restauran las exportaciones CSV ni los desplegables vacíos retirados; la paginación del área aparece únicamente cuando hay más páginas.
