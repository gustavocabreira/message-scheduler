#!/bin/bash

set -e

cd /var/www/html

# Align www-data UID/GID with the host user who owns the mounted volume,
# so Laravel can write to storage/logs without permission errors.
HOST_UID=$(stat -c '%u' /var/www/html)
HOST_GID=$(stat -c '%g' /var/www/html)

if [ "$HOST_UID" != "0" ] && [ "$HOST_UID" != "$(id -u www-data)" ]; then
    groupmod -g "$HOST_GID" www-data 2>/dev/null || true
    usermod -u "$HOST_UID" www-data 2>/dev/null || true
fi

echo "[entrypoint] Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader

echo "[entrypoint] Fixing storage permissions..."
chown -R www-data:www-data storage bootstrap/cache

echo "[entrypoint] Starting Supervisor..."
exec "$@"
