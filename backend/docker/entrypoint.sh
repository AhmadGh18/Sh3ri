#!/usr/bin/env bash
# Boot sequence for the Render container. Runs on every cold start.
# migrate is idempotent; a cache clear guards against config baked at build
# with the wrong DB_URL when the env var only arrives at run-time.
set -euo pipefail

cd /var/www/html

echo "→ php artisan config:clear"
php artisan config:clear || true

echo "→ php artisan migrate --force"
php artisan migrate --force || {
  echo "!! migrate failed — check DB_URL and Neon SSL settings"
  exit 1
}

echo "→ starting apache on port ${PORT:-8080}"
exec apache2-foreground
