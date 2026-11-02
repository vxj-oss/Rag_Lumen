#!/bin/sh
# Arranque del contenedor en Railway.
set -e
cd /var/www/html

# Railway indica el puerto por la variable PORT.
PORT="${PORT:-8080}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Directorios de trabajo. El Volume (si existe) llega con dueño root: se entrega a www-data.
PRIVADO="${FILESYSTEM_PRIVATE_ROOT:-/var/www/html/storage/app/private}"
mkdir -p "$PRIVADO" storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache "$PRIVADO"

# Cachés de configuración y vistas con las variables reales del entorno.
php artisan config:cache
php artisan view:cache

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/laravel.conf
