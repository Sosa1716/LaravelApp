FROM php:8.2-cli

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos de storage
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# Render pasa el puerto en $PORT
CMD bash -c "php artisan config:cache && \
             php artisan route:cache && \
             php artisan view:cache && \
             php artisan migrate --force && \
             php artisan db:seed --force && \
             php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"

