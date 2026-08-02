#!/bin/bash

# Limpiar caché previa de sesión de WhatsApp
rm -rf /var/www/html/wa-motor/.wwebjs_auth

# Iniciar motor de WhatsApp en segundo plano
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