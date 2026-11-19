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
RUN mkdir -p storage/logs \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
             bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 10000

# IMPORTANTE: usar comillas simples en el CMD para que $PORT se expanda
# en TIEMPO DE EJECUCIÓN (cuando Render ya lo tiene disponible),
# no en tiempo de build.
CMD ["/bin/bash", "-c", "php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT"]

