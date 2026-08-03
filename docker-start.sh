#!/bin/bash

# 1. Configurar puerto dinámico de Apache si Render asigna la variable $PORT
if [ -n "$PORT" ]; then
    echo "Configurando Apache en puerto $PORT..."
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/:80>/:$PORT>/g" /etc/apache2/sites-available/*.conf
fi

# 2. Garantizar permisos de escritura para www-data y node en carpetas clave
chmod -R 777 /var/www/html/public /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/wa-motor 2>/dev/null || true

# 3. Limpieza de cachés de Laravel
php artisan config:clear || true
php artisan view:clear || true

# 4. Iniciar migraciones y motor en segundo plano para no bloquear el puerto web
(php artisan migrate --force) &
(node /var/www/html/wa-motor/index.js) &

# 5. Iniciar Apache inmediatamente con exec en primer plano para responder al Health Check de Render en < 1s
exec apache2-foreground