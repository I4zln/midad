#!/bin/sh
# Container entrypoint: fill host-provided values, prepare Laravel, start Apache.
set -e
cd /var/www/html

# The host injects its public address (Render: RENDER_EXTERNAL_URL).
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# Accept either a Laravel key (base64:...) or any random secret from the host, turned into a stable 32-byte key.
case "$APP_KEY" in
  base64:*) ;;
  "") echo "APP_KEY is not set" >&2; exit 1 ;;
  *) export APP_KEY="$(php -r 'echo "base64:".base64_encode(hash("sha256", getenv("APP_KEY"), true));')" ;;
esac

# Apache listens on the port the host assigns.
PORT="${PORT:-10000}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan migrate --force
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize

# MIDAD_ADMIN_EMAIL (set in the host dashboard) makes that already registered account an admin.
if [ -n "$MIDAD_ADMIN_EMAIL" ]; then php artisan midad:make-admin "$MIDAD_ADMIN_EMAIL" || true; fi
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
