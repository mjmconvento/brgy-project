#!/bin/sh
#
# Container entrypoint for the Barangay Profiling System PHP image.
#
# Responsibilities, in order:
#   1. make the writable Laravel directories exist and be writable
#   2. materialise .env from .env.example on a fresh volume (dev only)
#   3. generate APP_KEY when it is still empty
#   4. block until the database accepts TCP connections
#   5. optionally migrate (RUN_MIGRATIONS=true)
#   6. warm the config/route/view caches in production
#   7. render the nginx vhost when this image serves HTTP itself
#   8. exec the real command (php-fpm, supervisord, queue:work, ...)
#
set -e

APP_DIR="${APP_DIR:-/var/www/html}"
cd "$APP_DIR"

log() {
    echo "[entrypoint] $*"
}

# ---------------------------------------------------------------------------
# 1. Writable directories
# ---------------------------------------------------------------------------
for dir in \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
do
    mkdir -p "$dir" 2>/dev/null || true
done

chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# ---------------------------------------------------------------------------
# 2. .env
# ---------------------------------------------------------------------------
# A managed platform (Render, Cloud Run, ...) injects the whole configuration as
# real environment variables, and APP_KEY is the marker that this has happened.
# Copying .env.example there would be actively harmful: it carries the compose
# service names (DB_HOST=postgres, REDIS_HOST=redis, MAIL_HOST=mailpit), and any
# variable the platform does not set would silently fall back to them.
if [ -f .env ]; then
    :
elif [ -n "${APP_KEY:-}" ]; then
    log "APP_KEY is set in the environment, not seeding a .env"
elif [ -f .env.example ]; then
    log "no .env found, seeding it from .env.example"
    cp .env.example .env
fi

# In the dev workflow the project is bind-mounted over /var/www/html, so the
# vendor/ baked into the image is shadowed by the host one. Until `make install`
# has run there is no autoloader and every artisan call would explode; php-fpm
# itself is perfectly happy to start, so warn instead of dying.
vendor_ready=true
if [ ! -f vendor/autoload.php ]; then
    vendor_ready=false
    log "WARNING: vendor/autoload.php is missing — skipping every artisan step."
    log "         Install the dependencies with: make install"
fi

# ---------------------------------------------------------------------------
# 3. APP_KEY
# ---------------------------------------------------------------------------
app_key="${APP_KEY:-}"
if [ -z "$app_key" ] && [ -f .env ]; then
    app_key="$(sed -n 's/^APP_KEY=//p' .env | head -n 1 | tr -d '\r\n\"')"
fi

if [ -z "$app_key" ] && [ "$vendor_ready" = "true" ]; then
    log "APP_KEY is empty, generating one"
    php artisan key:generate --force --no-interaction
fi

# ---------------------------------------------------------------------------
# 4. Wait for the database
# ---------------------------------------------------------------------------
db_connection="${DB_CONNECTION:-pgsql}"

# Per-driver default, because DB_PORT is usually left unset. Only pgsql and
# sqlite are reachable: config/database.php defines no other connection and the
# image has no pdo_mysql, so anything else fails at connect time regardless of
# which port we waited on.
case "$db_connection" in
    sqlite) db_default_port= ;;
    *) db_default_port=5432 ;;
esac

db_host="${DB_HOST:-postgres}"
db_port="${DB_PORT:-$db_default_port}"

# A managed database is addressed by one URL and is always up; there is no
# sibling container to race. DB_URL also overrides host/port in Laravel's
# config, so the DB_HOST default above would have us wait on the wrong address
# — for Neon, on the literal host `postgres`, for the full 60 seconds, and then
# exit 1 before php-fpm ever starts.
if [ -n "${DB_URL:-}" ]; then
    log "DB_URL is set, skipping the database wait"
elif [ "$db_connection" != "sqlite" ] && [ "${SKIP_DB_WAIT:-false}" != "true" ]; then
    log "waiting for ${db_connection} at ${db_host}:${db_port}"

    attempt=1
    max_attempts=60
    while [ "$attempt" -le "$max_attempts" ]; do
        if php -r '
            $host = $argv[1];
            $port = (int) $argv[2];
            $socket = @fsockopen($host, $port, $errno, $errstr, 2);
            if ($socket === false) {
                exit(1);
            }
            fclose($socket);
            exit(0);
        ' "$db_host" "$db_port"; then
            log "database is accepting connections after ${attempt} attempt(s)"
            break
        fi

        if [ "$attempt" -eq "$max_attempts" ]; then
            log "ERROR: database at ${db_host}:${db_port} did not become reachable"
            log "       after ${max_attempts} attempts (~${max_attempts}s)."
            log "       Check DB_HOST/DB_PORT and that the 'postgres' service is healthy:"
            log "         docker compose ps"
            log "         docker compose logs postgres"
            exit 1
        fi

        attempt=$((attempt + 1))
        sleep 1
    done
fi

# ---------------------------------------------------------------------------
# 5. Migrations (opt-in)
# ---------------------------------------------------------------------------
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    if [ "$vendor_ready" != "true" ]; then
        log "ERROR: RUN_MIGRATIONS=true but vendor/autoload.php is missing."
        log "       Run 'make install' (composer install) before booting with"
        log "       migrations enabled."
        exit 1
    fi

    log "RUN_MIGRATIONS=true, running migrations"
    php artisan migrate --force --no-interaction
fi

# ---------------------------------------------------------------------------
# 6. Production cache warm-up
# ---------------------------------------------------------------------------
if [ "$vendor_ready" != "true" ]; then
    :
elif [ "${APP_ENV:-production}" = "production" ]; then
    log "APP_ENV=production, caching config, routes and views"
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
else
    php artisan config:clear --no-interaction >/dev/null 2>&1 || true
fi

# ---------------------------------------------------------------------------
# 7. nginx vhost (single-container `render` stage only)
# ---------------------------------------------------------------------------
# nginx has no equivalent of env(), and the platform only tells you the port at
# runtime, so the vhost ships as a template. Only ${PORT} is named: leaving
# envsubst to substitute everything would eat nginx's own $uri, $document_root
# and $fastcgi_script_name.
#
# http.d, not conf.d: on Alpine `conf.d/*.conf` is included in nginx's main
# context, where a `server` block is a syntax error.
if [ -f /etc/nginx/templates/default.conf.template ]; then
    log "rendering the nginx vhost on port ${PORT:-8080}"
    PORT="${PORT:-8080}" envsubst '${PORT}' \
        < /etc/nginx/templates/default.conf.template \
        > /etc/nginx/http.d/default.conf
fi

# ---------------------------------------------------------------------------
# 8. Hand over
# ---------------------------------------------------------------------------
log "starting: $*"
exec "$@"
