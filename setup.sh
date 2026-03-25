#!/usr/bin/env bash
set -euo pipefail

FORCE=false
for arg in "$@"; do
  [[ "$arg" == "--force" ]] && FORCE=true
done

# ── Backend .env ─────────────────────────────────────────────────────────────
if [[ ! -f backend/.env || "$FORCE" == "true" ]]; then
  echo "==> Copying backend/.env.example → backend/.env"
  cp backend/.env.example backend/.env
else
  echo "==> backend/.env already exists (use --force to overwrite)"
fi

echo "==> Building images..."
docker compose build

echo "==> Starting services..."
docker compose up -d postgres redis

echo "==> Waiting for PostgreSQL to be ready..."
until docker compose exec -T postgres pg_isready -U postgres > /dev/null 2>&1; do
  sleep 1
done

echo "==> Installing PHP dependencies..."
docker compose run --rm backend composer install --no-interaction --prefer-dist

echo "==> Generating application key..."
docker compose run --rm backend php artisan key:generate --force

echo "==> Running landlord migrations..."
docker compose run --rm backend php artisan migrate --path=database/migrations/landlord --force

echo "==> Running tenant migrations..."
docker compose run --rm backend php artisan migrate --force

echo "==> Starting all services..."
docker compose up -d

echo ""
echo "✓ Setup complete."
echo ""
echo "  API:      http://api.localhost.com"
echo "  App:      http://app.localhost.com"
echo ""
echo "  Add to /etc/hosts if needed:  make hosts"
