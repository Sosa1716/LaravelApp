#!/usr/bin/env bash
# =============================================================
# Script de build para Render.com
# Render lo ejecuta automáticamente en cada deploy.
# Configurar en el panel de Render:
#   Build Command: bash render-build.sh
#   Start Command: php artisan serve --host=0.0.0.0 --port=$PORT
# =============================================================
set -e

echo "==> Instalando dependencias PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Generando APP_KEY si no existe..."
php artisan key:generate --force

echo "==> Optimizando configuración..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Ejecutando migraciones..."
php artisan migrate --force

echo "==> Ejecutando seeders (solo en primer deploy)..."
php artisan db:seed --force

echo "==> Creando enlace de storage..."
php artisan storage:link || true

echo "==> Build completado ✓"

