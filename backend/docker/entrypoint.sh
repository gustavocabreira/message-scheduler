#!/bin/bash

set -e

cd /var/www/html

echo "[entrypoint] Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader

echo "[entrypoint] Starting Supervisor..."
exec "$@"
