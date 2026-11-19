FROM php:8.2-cli

# Instalar extensiones necesarias para PostgreSQL y Laravel
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias PHP (sin dev, optimizado)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Crear directorios necesarios y dar permisos
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# Al arrancar el contenedor:
# 1. Migra la base de datos
# 2. Siembra datos base (updateOrCreate, es idempotente)
# 3. Levanta el servidor en el puerto que Render asigna
CMD bash -c "\
    php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"

