#!/bin/bash

# Iniciar motor de WhatsApp en segundo plano (mantiene la sesión en .wwebjs_auth)
node /var/www/html/wa-motor/index.js &

# Ejecutar migraciones
php artisan migrate --force

# Limpiar caché de vistas para evitar HTML antiguo en Blade
php artisan view:clear
php artisan config:clear

# Iniciar Reverb en segundo plano
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Iniciar Apache en primer plano
apache2-foreground