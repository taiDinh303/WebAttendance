#!/bin/sh
set -eu
umask 027

# Render assigns the backend URL after creation; use it during initial bootstrap.
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-}}"
export FRONTEND_URL="${FRONTEND_URL:-$APP_URL}"

# Cloud startup must never fall back to local credentials or unverified TLS.
: "${APP_KEY:?Set a persistent Laravel APP_KEY in Render}"
: "${APP_URL:?Set APP_URL}"
: "${FRONTEND_URL:?Set FRONTEND_URL}"
: "${DB_HOST:?Set the new Aiven host}"
: "${DB_PORT:?Set the Aiven port}"
: "${DB_DATABASE:?Set the AttendanceWeb database name}"
: "${DB_USERNAME:?Set the AttendanceWeb database user}"
: "${DB_PASSWORD:?Set the AttendanceWeb database password}"
: "${MYSQL_ATTR_SSL_CA:?Mount the Aiven CA certificate as a Render secret file}"
test -r "$MYSQL_ATTR_SSL_CA" || { echo 'Aiven CA certificate is not readable.' >&2; exit 1; }
install -o www-data -g www-data -m 0400 "$MYSQL_ATTR_SSL_CA" /var/www/html/storage/framework/aiven-ca.pem
export MYSQL_ATTR_SSL_CA=/var/www/html/storage/framework/aiven-ca.pem
php -r 'if (openssl_x509_read(file_get_contents(getenv("MYSQL_ATTR_SSL_CA"))) === false) { fwrite(STDERR, "Invalid Aiven CA certificate.\n"); exit(1); }'
test "${APP_ENV:-}" = production || { echo 'Cloud APP_ENV must be production.' >&2; exit 1; }
test "${APP_DEBUG:-false}" = false || { echo 'Cloud APP_DEBUG must be false.' >&2; exit 1; }

su -s /bin/sh www-data -c 'php artisan config:cache --no-interaction'
if [ "${RUN_MIGRATIONS:-false}" = true ]; then
    su -s /bin/sh www-data -c 'php artisan migrate --force --no-interaction'
fi
if [ "${VISUAL_DEMO_ENABLED:-false}" = true ]; then
    su -s /bin/sh www-data -c 'php artisan demo:visual --no-interaction'
fi
if [ "${PORTFOLIO_DEMO_ENABLED:-false}" = true ]; then
    su -s /bin/sh www-data -c 'php artisan demo:portfolio --no-interaction'
fi
su -s /bin/sh www-data -c 'php artisan route:cache --no-interaction'

http_port="${PORT:-10000}"
case "$http_port" in
    ''|*[!0-9]*) echo 'PORT must be numeric.' >&2; exit 1 ;;
esac
sed -i "s/^Listen 80$/Listen $http_port/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$http_port>/" /etc/apache2/sites-available/000-default.conf

# The scheduler runs only while this Free web instance is awake.
if [ "${RUN_SCHEDULER:-true}" = true ]; then
    su -s /bin/sh www-data -c 'php artisan schedule:work --no-interaction' &
fi
exec docker-php-entrypoint "$@"
