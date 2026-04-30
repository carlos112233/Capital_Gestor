# Usamos una imagen oficial de PHP con Apache
FROM php:8.3-apache

# Instalar dependencias del sistema y extensiones de PHP para Postgres y Node
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql pcntl zip

# Habilitar el módulo rewrite de Apache (necesario para Laravel)
RUN a2enmod rewrite

# Instalar Node.js (necesario para Vite/npm)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Copiar los archivos del proyecto al contenedor
COPY . .

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar dependencias de PHP
RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN composer dump-autoload

RUN composer update

# ... después de composer install ...
RUN php artisan storage:link

# Instalar dependencias de Node y compilar assets (Vite)
RUN npm install && npm run build

# Configurar Apache para que apunte a la carpeta /public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Habilitar el módulo rewrite de Apache para Laravel
RUN a2enmod rewrite

# Dar permisos a las carpetas de almacenamiento
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Crear un script de inicio (esto es clave para correr Apache y Reverb juntos)
COPY docker-start.sh /usr/local/bin/docker-start.sh
RUN chmod +x /usr/local/bin/docker-start.sh

# Exponer el puerto 80 (web) y el 8080 (Websockets de Reverb)
EXPOSE 80 8080

# Usar el script de inicio
CMD ["/usr/local/bin/docker-start.sh"]