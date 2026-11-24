#!/bin/sh
# =============================================================================
#  Arranque del contenedor: valida configuración, migra, (opcional) siembra,
#  cachea configuración y levanta FrankenPHP en $PORT.
# =============================================================================
set -eu

log() { echo "[entrypoint] $*"; }

: "${PORT:=8080}"

if [ -z "${APP_KEY:-}" ]; then
    log "ERROR: APP_KEY no está definida. Genera una con 'php artisan key:generate --show'." >&2
    exit 1
fi

# SQLite (staging/local): el archivo debe existir antes de migrar.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    db_file="${DB_DATABASE:-/app/database/database.sqlite}"
    [ -f "$db_file" ] || { log "Creando base SQLite en $db_file"; touch "$db_file"; }
fi

# La BD gestionada puede tardar en aceptar conexiones tras un reinicio.
attempt=1
until php artisan migrate --force --no-interaction; do
    if [ "$attempt" -ge 10 ]; then
        log "ERROR: las migraciones fallaron tras $attempt intentos." >&2
        exit 1
    fi
    log "Migración fallida (intento $attempt/10), reintentando en 3s..."
    attempt=$((attempt + 1))
    sleep 3
done

# Los seeders sobrescriben órdenes 1-4 y crean usuarios con contraseña conocida:
# solo deben ejecutarse en entornos de prueba (ver docs/problemas.md, P-06).
if [ "${SEED_ON_DEPLOY:-false}" = "true" ]; then
    log "SEED_ON_DEPLOY=true -> ejecutando seeders"
    php artisan db:seed --force --no-interaction
fi

php artisan storage:link --no-interaction >/dev/null 2>&1 || true

# Momento de arranque de ESTA instancia: el pipeline lo usa para distinguir
# un redeploy del mismo commit de la instancia anterior (ver P-39).
APP_BOOTED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
export APP_BOOTED_AT

# Cachear con las variables de entorno reales del contenedor (no en el build).
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

log "Versión ${APP_VERSION:-dev} (${APP_ENV:-production}) escuchando en :${PORT}"

# Permite sobrescribir el comando (p. ej. `docker run imagen php artisan tinker`).
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

exec frankenphp php-server --listen ":${PORT}" --root /app/public
