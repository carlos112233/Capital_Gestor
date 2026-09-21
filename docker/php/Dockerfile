# ─────────────────────────────────────────────────────────────────────────────
# Dockerfile para PHP 8.2 FPM — Capital Gestor (Laravel)
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema necesarias para Laravel y las extensiones de PHP
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    zip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# Instalar extensiones de PHP requeridas por Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
        pcntl \
        bcmath \
        intl \
        mbstring \
        xml \
        opcache

# Instalar Redis extension para caché
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Crear usuario no privilegiado para mayor seguridad
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/bash -D www

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar el código fuente
COPY --chown=www:www . .

# Instalar dependencias de PHP (sin dev, optimizado para producción)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Crear carpetas de almacenamiento necesarias y asignar permisos correctos
RUN mkdir -p storage/app/temp_pdfs \
             storage/app/public \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 755 storage bootstrap/cache

# Cambiar al usuario no privilegiado
USER www

# PHP-FPM escucha en el puerto 9000
EXPOSE 9000

CMD ["php-fpm"]

