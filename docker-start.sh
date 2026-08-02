#!/bin/bash

# Configurar puerto dinámico de Apache si Render asigna la variable $PORT
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/:80>/:$PORT>/g" /etc/apache2/sites-available/*.conf
fi

# Limpiar caché de vistas e intrucciones de Laravel
php artisan view:clear
php artisan config:clear

# Ejecutar migraciones de forma segura
php artisan migrate --force || true

# Iniciar motor de WhatsApp en segundo plano
node /var/www/html/wa-motor/index.js &

# Iniciar Reverb websockets en segundo plano
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Iniciar Apache en primer plano para servir tráfico web
apache2-foreground