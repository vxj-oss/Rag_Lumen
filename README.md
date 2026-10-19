# Proyecto PPP2

Aplicación Laravel para la gestión de proyectos, tareas, empleados, clientes, normativa y consultas sobre documentos mediante RAG.

## Accesos rápidos

- [Mapa del proyecto: dónde encontrar cada cosa](docs/mapa-del-proyecto.md)
- [Documentación](docs/README.md)
- [Instalación local](docs/instalacion.md)
- [Despliegue en Hostinger](docs/despliegue-hostinger.md)
- [Rutas de las pantallas](routes/web.php)
- [Vistas por módulo](resources/views/)
- [Controladores por módulo](app/Http/Controllers/Web/)
- [Pruebas](tests/)

## Organización

Las carpetas principales siguen la estructura de Laravel. Dentro de ellas, los archivos se agrupan por módulo y mantienen los nombres existentes en inglés; Normativa conserva su nombre de dominio.

- `app/`: comportamiento, validaciones, modelos y permisos.
- `resources/views/`: pantallas y componentes Blade.
- `resources/js/modules/`: JavaScript propio de los módulos.
- `resources/css/`: estilos.
- `database/`: migraciones, datos iniciales y fábricas de pruebas.
- `docs/`: guías actuales y documentos históricos.
- `tests/`: pruebas automatizadas.

Las dependencias, cachés y ayudas del editor se ocultan en el explorador de VS Code mediante la configuración local `.vscode/settings.json`. No se borran del disco.