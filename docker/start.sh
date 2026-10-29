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

# As many PHP workers as the memory holds (each uses roughly 30-40 MB; Render's free and Starter plans have 512 MB).
# Apache's default of 150 would run out of memory under a crowd; extra visitors now wait a moment in the queue instead.
# A short keep-alive stops idle connections from holding those few workers.
cat > /etc/apache2/conf-enabled/midad-workers.conf <<EOF
<IfModule mpm_prefork_module>
    StartServers 3
    MinSpareServers 2
    MaxSpareServers 6
    MaxRequestWorkers ${APACHE_MAX_WORKERS:-10}
    MaxConnectionsPerChild 2000
</IfModule>
KeepAliveTimeout 2
EOF
# Never let this tuning stop the site: if Apache rejects it, start with the defaults.
apache2ctl -t >/dev/null 2>&1 || { echo "Apache rejected midad-workers.conf; using the defaults" >&2; rm -f /etc/apache2/conf-enabled/midad-workers.conf; }

php artisan migrate --force
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize

# MIDAD_ADMIN_EMAIL (set in the host dashboard) makes that already registered account an admin.
if [ -n "$MIDAD_ADMIN_EMAIL" ]; then php artisan midad:make-admin "$MIDAD_ADMIN_EMAIL" || true; fi
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
