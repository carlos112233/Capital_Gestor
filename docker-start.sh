#!/bin/bash

# Ejecutar migraciones (opcional pero recomendado)
php artisan migrate --force

# Iniciar Reverb en segundo plano
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Iniciar Apache en primer plano
apache2-foreground