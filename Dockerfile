# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Barangay Profiling System — multi-stage image
#
#   vendor  : composer dependencies (production set) + optimized autoloader
#   assets  : Vite/Tailwind build output (public/build)
#   app     : php:8.5.10-fpm-alpine runtime, non-root, opcache + JIT enabled
#
# Build:  docker compose build
# Run  :  docker compose up
# ---------------------------------------------------------------------------

# ---------------------------------------------------------------------------
# Stage 1 — PHP dependencies
# ---------------------------------------------------------------------------
FROM php:8.5.10-fpm-alpine AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

RUN apk add --no-cache git unzip libzip icu-libs \
    && apk add --no-cache --virtual .vendor-deps $PHPIZE_DEPS icu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" intl zip \
    && apk del .vendor-deps

WORKDIR /app

# composer.lock is optional: the image must build before the lock file exists
# (first bootstrap) and use it verbatim once it does.
COPY composer.json composer.lock* ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-progress \
        --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-scripts

# ---------------------------------------------------------------------------
# Stage 2 — Frontend assets
# ---------------------------------------------------------------------------
FROM node:24-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* .npmrc* ./

RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources

# Tailwind's `@source` directives in resources/css/app.css scan Laravel's
# published pagination views and the compiled Blade cache. Both live outside
# `resources/`, so make those paths exist before the build runs — otherwise the
# pagination utility classes are silently omitted from the stylesheet.
COPY --from=vendor \
    /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN mkdir -p public storage/framework/views && npm run build

# ---------------------------------------------------------------------------
# Stage 3 — Application runtime
# ---------------------------------------------------------------------------
FROM php:8.5.10-fpm-alpine AS app

ARG UID=1000
ARG GID=1000

# COMPOSER_HOME: $HOME is /var/www/html, i.e. the dev bind mount — keep
# composer's cache and config out of the developer's working tree.
ENV APP_ENV=production \
    APP_DEBUG=false \
    RUN_MIGRATIONS=false \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_HOME=/tmp/composer

# Runtime libraries (shared objects the extensions link against) plus the small
# set of tools the entrypoint and healthcheck rely on.
RUN apk add --no-cache \
        bash \
        curl \
        tzdata \
        su-exec \
        netcat-openbsd \
        icu-libs \
        libpq \
        libzip

# PHP extensions. Build headers live in a virtual package that is dropped again
# in the same layer so nothing but the compiled modules survives.
#
# Not listed on purpose:
#   opcache  — statically compiled into php:8.5-fpm-alpine already, so
#              `docker-php-ext-install opcache` produces no module and fails.
#              docker/php/opcache.ini still configures it.
#   mbstring — likewise already built in on this image.
#   gd/exif  — the application does no image processing.
#   pdo_mysql — the application is PostgreSQL-only; see config/database.php.
RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
        icu-dev \
        libpq-dev \
        libzip-dev; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        bcmath \
        intl \
        zip \
        pcntl; \
    yes '' | pecl install redis; \
    docker-php-ext-enable redis; \
    rm -rf /tmp/pear; \
    apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP / FPM configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

# Non-root runtime user. UID/GID are build args so host bind mounts on Linux
# line up with the developer's account.
RUN set -eux; \
    addgroup -g "${GID}" -S app; \
    adduser -u "${UID}" -S -G app -h /var/www/html -s /bin/bash app

WORKDIR /var/www/html

COPY --chown=app:app . .
COPY --from=vendor --chown=app:app /app/vendor ./vendor
COPY --from=assets --chown=app:app /app/public/build ./public/build

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN set -eux; \
    chmod +x /usr/local/bin/entrypoint.sh; \
    mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache; \
    chown -R app:app /var/www/html/storage /var/www/html/bootstrap/cache; \
    chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache

USER app

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD nc -z 127.0.0.1 9000 || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Stage 4 — Single-container deployable (Render, Cloud Run, Container Apps)
#
# The `app` stage above speaks FastCGI on 9000 and nothing but nginx can talk
# to it. A free PaaS gives you ONE container and routes HTTP to ONE injected
# port, so a platform pointed at `app` would connect and get bytes it cannot
# parse. This stage puts nginx in front of php-fpm over loopback and answers
# HTTP on ${PORT}.
#
# compose never builds this stage: its `app` service targets `app` and
# bind-mounts the source over /var/www/html, which would shadow the assets and
# vendor baked in here.
#
# Deliberately NOT `config:cache` at build time — see docker/php/entrypoint.sh.
# `php artisan config:cache` freezes the result of every env() call, and the
# build has no platform environment at all, so the published image would ignore
# everything Render injects. The entrypoint caches at start instead.
# ---------------------------------------------------------------------------
FROM app AS render

USER root

# PORT is a documented default, not the real value: the platform overrides it.
ENV PORT=8080 \
    SKIP_DB_WAIT=true

# Alpine's nginx package is laid out differently from Debian's, and every line
# below is load-bearing for running it as a non-root user:
#   * conf.d/*.conf is included in the MAIN context and http.d/*.conf inside
#     `http`, so the vhost belongs in http.d — putting it in conf.d fails with
#     '"server" directive is not allowed here'.
#   * there is no `pid` directive to rewrite; the compiled default is
#     /run/nginx/nginx.pid, which `app` cannot create.
#   * error_log and access_log point at /var/log/nginx, which nginx opens at
#     startup and `app` cannot write.
#   * `user nginx;` only means anything when the master runs as root.
RUN set -eux; \
    apk add --no-cache nginx supervisor gettext; \
    sed -i \
        -e '1i pid /tmp/nginx.pid;' \
        -e 's|^user nginx;|# `user` requires a root master process; this image runs as app.|' \
        -e 's|error_log /var/log/nginx/error.log|error_log /dev/stderr|' \
        -e 's|access_log /var/log/nginx/access.log|access_log /dev/stdout|' \
        /etc/nginx/nginx.conf; \
    # `nginx -t` validates nginx.conf at build time — but it runs as root and
    # writes the pid file declared above. That file would be committed into the
    # layer owned by root, and /tmp is sticky, so the unprivileged runtime user
    # could then neither overwrite nor unlink it: nginx dies at boot with
    # 'open() "/tmp/nginx.pid" failed (13: Permission denied)'.
    nginx -t -c /etc/nginx/nginx.conf; \
    rm -f /tmp/nginx.pid; \
    rm -f /etc/nginx/http.d/default.conf; \
    mkdir -p /etc/nginx/templates; \
    chown -R app:app /etc/nginx/http.d

COPY docker/nginx/render.conf.template /etc/nginx/templates/default.conf.template
COPY docker/php/supervisord.conf /etc/supervisord.conf

# Sized for Render's Free plan: 0.1 CPU / 512 MB for the whole container. The
# dev pool reserves 192 MB of opcache and allows 20 workers at 512 MB each,
# which OOM-kills this instance and surfaces as intermittent 502s.
COPY docker/php/render-php.ini /usr/local/etc/php/conf.d/zz-render.ini
COPY docker/php/render-fpm.conf /usr/local/etc/php-fpm.d/zzz-render.conf

USER app

EXPOSE 8080

# The platform polls /up over HTTP; the FastCGI probe of the `app` stage would
# report healthy while nginx was dead.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS "http://127.0.0.1:${PORT}/up" >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
