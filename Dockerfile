# syntax=docker/dockerfile:1
# =============================================================================
#  El Pinguin API — imagen de producción (Laravel 12 + FrankenPHP)
#
#  Etapa 1 (vendor):  instala dependencias de Composer sin dev.
#  Etapa 2 (runtime): FrankenPHP (Caddy + PHP 8.3) con usuario no-root.
#
#  Variables de ejecución relevantes:
#    PORT            puerto HTTP (Render lo inyecta; por defecto 8080)
#    APP_KEY         obligatoria
#    SEED_ON_DEPLOY  "true" solo en entornos efímeros (staging); en prod "false"
# =============================================================================

ARG PHP_VERSION=8.3

# ---------------------------------------------------------------------------
# Etapa 1: dependencias PHP
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts/--no-autoloader: el código aún no está copiado; el autoloader
# optimizado se genera en la etapa final. --ignore-platform-reqs porque las
# extensiones se instalan en la imagen runtime, no en esta.
RUN composer install \
        --no-dev --no-scripts --no-autoloader \
        --prefer-dist --no-interaction --no-progress \
        --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Etapa 2: runtime
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php${PHP_VERSION}-bookworm AS runtime

ARG APP_VERSION=dev
ARG UID=10001

LABEL org.opencontainers.image.title="el-pinguin-api" \
      org.opencontainers.image.description="API REST de El Pinguin (Laravel 12 + FrankenPHP)" \
      org.opencontainers.image.licenses="MIT"

# Extensiones: PostgreSQL (prod), SQLite (staging/local), resto para Laravel.
RUN install-php-extensions pdo_pgsql pdo_sqlite intl zip opcache bcmath pcntl \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .

# Autoloader optimizado + package:discover (post-autoload-dump) y directorios
# de runtime que no se versionan (su ausencia rompía view:cache, ver P-07).
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction \
    && mkdir -p storage/app/public storage/logs \
                storage/framework/cache/data storage/framework/sessions storage/framework/views \
                bootstrap/cache \
    && rm -f /usr/bin/composer

# Usuario sin privilegios: solo puede escribir en storage, bootstrap/cache,
# database (SQLite de staging) y los directorios de datos de Caddy.
#
# setcap -r: la imagen base marca el binario con cap_net_bind_service (para
# puertos <1024). Plataformas como Render ejecutan los contenedores sin esa
# capacidad y el kernel se niega a ejecutar un binario que la exige:
# "exec: frankenphp: Operation not permitted" (exit 126). Escuchamos en un
# puerto alto ($PORT), así que no la necesitamos (docs/problemas.md, P-37).
RUN setcap -r /usr/local/bin/frankenphp \
    && useradd --uid ${UID} --no-create-home --shell /usr/sbin/nologin app \
    && chown -R app:app storage bootstrap/cache database /data/caddy /config/caddy \
    && chmod -R ug+rwX,o-rwx storage bootstrap/cache database

COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_VERSION=${APP_VERSION} \
    LOG_CHANNEL=stderr \
    PORT=8080

USER app

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS "http://127.0.0.1:${PORT}/up" > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
