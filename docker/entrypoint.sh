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

# Migraciones al arrancar (idempotente). Así la base queda al día aunque el pre-deploy no se haya ejecutado.
# Se ejecuta como www-data para que los logs/cachés que genere no queden como root.
su -s /bin/sh www-data -c "php artisan migrate --force" || echo "ADVERTENCIA: las migraciones fallaron; revisa la conexión a la base de datos."

# Diagnóstico de Apache (visible en los logs del despliegue).
echo "== Apache: MPM en mods-enabled =="; ls -la /etc/apache2/mods-enabled | grep -i mpm || true
echo "== Apache: apache2ctl -t =="; apache2ctl -t 2>&1 | head -5 || true
echo "== Apache: modulos MPM cargados =="; apache2ctl -M 2>&1 | grep -i mpm || true
echo "== Variables relacionadas =="; env | grep -i -E "apache|mpm" || true
echo "== ports.conf =="; cat /etc/apache2/ports.conf

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/laravel.conf
