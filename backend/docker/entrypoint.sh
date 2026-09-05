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

# Seed on every boot — all seeders use updateOrCreate / firstOrCreate so
# this is idempotent. Free-tier Render doesn't have Shell access, so we
# can't run `db:seed` manually — bake it into the boot instead.
# Cost: ~1-2s of extra cold-start. Wildly worth it.
echo "→ php artisan db:seed --force"
php artisan db:seed --force || echo "!! seed failed (continuing — data may already be present)"

echo "→ starting apache on port ${PORT:-8080}"
exec apache2-foreground
