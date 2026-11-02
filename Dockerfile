# syntax=docker/dockerfile:1
#
# Imagen para Railway: un solo contenedor con Apache (web), el worker de colas
# (documentos del RAG) y el scheduler (alertas y respaldos), gestionados por supervisord.
# Se usa un único servicio porque los archivos subidos (firmas, evidencias, documentos)
# viven en un Volume de Railway, que solo puede montarse en un servicio.

# ---------- 1) Dependencias PHP ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts --ignore-platform-reqs

# ---------- 2) Assets (Vite + Tailwind) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY app/Support ./app/Support
COPY public ./public
RUN npm run build

# ---------- 3) Imagen final ----------
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libonig-dev supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl mbstring bcmath opcache pcntl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Apache sirve public/ y se admite .htaccess de Laravel
RUN sed -ri 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/laravel.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN tr -d '\015' < /usr/local/bin/entrypoint.sh > /tmp/entrypoint.sh && mv /tmp/entrypoint.sh /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
RUN chown -R www-data:www-data storage bootstrap/cache

# CMD (no ENTRYPOINT) para que el "pre-deploy command" de Railway pueda reemplazarlo.
CMD ["/usr/local/bin/entrypoint.sh"]
