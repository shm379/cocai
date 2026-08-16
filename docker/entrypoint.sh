#!/usr/bin/env sh
set -e

mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

if [ -d /etc/apache2 ]; then
  printf 'SetEnvIf X-Forwarded-Proto "^https$" HTTPS=on\nRemoteIPHeader X-Forwarded-For\nRemoteIPInternalProxy 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16 127.0.0.1\n' > /etc/apache2/conf-available/ssl-proxy.conf
  a2enmod remoteip headers 2>/dev/null || true
  a2enconf ssl-proxy 2>/dev/null || true
fi

if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "APP_KEY is required" ]; then
  echo "APP_KEY is not set or invalid. Generating temporary application key..."
  export APP_KEY=$(php artisan key:generate --show --no-interaction)
fi

if [ "${RUN_STORAGE_LINK:-true}" = "true" ]; then
  php artisan storage:link --no-interaction || true
fi

php artisan config:cache --no-interaction
php artisan route:cache --no-interaction || true
php artisan view:cache --no-interaction || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "Checking database readiness..."
  for i in $(seq 1 30); do
    if php -r '
      $host = getenv("DB_HOST") ?: "mysql";
      $port = getenv("DB_PORT") ?: "3306";
      $conn = @fsockopen($host, (int)$port, $errno, $errstr, 2);
      if ($conn) { fclose($conn); exit(0); }
      exit(1);
    '; then
      echo "Database connection established."
      break
    fi
    echo "Waiting for database ($i/30)..."
    sleep 2
  done
  php artisan migrate --force --no-interaction || echo "Warning: Migration failed, continuing startup."
fi

exec "$@"
