# Instalación local

## Requisitos

PHP compatible con `composer.json` (actualmente ^8.3), Composer, Node.js y npm compatibles con las dependencias del proyecto, y una base de datos configurada en `.env`.

## Preparación

1. Ejecuta `composer install` y `npm install` en la raíz.
2. Si no existe `.env`, copia `.env.example` a `.env`.
3. Configura la conexión de base de datos y los servicios que utilizarás en `.env`.
4. Para una instalación nueva, genera la clave con `php artisan key:generate`. Conserva la clave de una instalación existente.
5. Aplica las migraciones con `php artisan migrate` sobre la base de datos elegida para esta instalación.
6. Compila los recursos con `npm run build`.

Revisa los archivos de `database/seeders/` antes de cargar datos de demostración. El comando de creación de administrador está en `app/Console/Commands/CreateAdminCommand.php`; consulta sus opciones con `php artisan help app:create-admin`.

## Desarrollo

`composer run dev` inicia el servidor, el proceso de colas, los registros y Vite según los scripts definidos en `composer.json`. Los proveedores de documentos y chat se configuran en `config/rag.php` y `.env`.

## Verificación

- `php artisan route:list`: comprueba el registro de rutas.
- `php artisan view:cache`: comprueba la compilación de las vistas Blade.
- `npm run build`: compila estilos y JavaScript.
- `php artisan test`: ejecuta las pruebas; `phpunit.xml` configura SQLite en memoria y requiere la extensión PDO SQLite de PHP. Si la extensión está instalada pero no activada, puedes cargarla solo para la comprobación con `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --configuration phpunit.xml`.

## Datos que debes conservar

No reemplaces el `.env` ni regeneres la clave de una instalación existente para reorganizar archivos. Respalda la base de datos y `storage/app/`.