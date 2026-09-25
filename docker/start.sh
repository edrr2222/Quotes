#!/bin/bash
set -e

# En Render, APP_KEY, DATABASE_URL, DB_CONNECTION, ANTHROPIC_API_KEY, etc. llegan como
# variables de entorno del servicio (ver README.md, sección "Desplegar en Render").

php artisan db:prepare-schema
php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
