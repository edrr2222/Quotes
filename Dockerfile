# syntax=docker/dockerfile:1

# ---------- Etapa 1: build de los assets (React/Inertia) ----------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY . .
RUN npm run build

# ---------- Etapa 2: PHP + dependencias de Composer ----------
FROM php:8.3-fpm-alpine AS app

RUN apk add --no-cache \
        nginx supervisor bash git unzip libpng-dev libzip-dev oniguruma-dev postgresql-dev \
        poppler-utils \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 10000
CMD ["/start.sh"]
