#!/usr/bin/env sh
set -e

mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY is required. Generate one with: php artisan key:generate --show" >&2
  exit 1
fi

if [ "${RUN_STORAGE_LINK:-true}" = "true" ]; then
  php artisan storage:link --no-interaction || true
fi

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction || true
php artisan view:cache --no-interaction || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

exec "$@"
