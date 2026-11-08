# Tech Steering

## Stack consolidado

- PHP 8.2+ (`composer.json:6-35`), contenedor Apache/PHP 8.3 en `Dockerfile:1-56`.
- Laravel 12 (`composer.json:6-35`).
- MySQL 8.0 con Docker (`docker-compose.yml:39-62`).
- Nginx como front-end (`docker-compose.yml:5-17`).
- Vite/Tailwind para frontend (`package.json:1-20`).
- Node.js para `wa-motor` y scripts adicionales (`Dockerfile:14-30`).

## Comandos de arranque

### Windows

El proyecto usa un arranque Docker local y volumenes persistentes. La convención esperada del entorno es:

```bat
docker compose up --build -d
```

Se identifica la ruta base y el dominio local `http://TallerVelazque.com` en la configuración del despliegue del proyecto, aunque la evidencia explícita del archivo de Windows no se ha encontrado en este repo. `docker-compose.yml:1-95`.

### Laravel

```bash
php artisan migrate
php artisan route:list
php artisan test
php artisan optimize:clear
```

## Reglas de cacheo

- `AppServiceProvider` inyecta `AppSetting` en todas las vistas con `View::composer('*', ...)`: `app/Providers/AppServiceProvider.php:24-41`.
- `AppSetting::clearSettingsCache()` invalida la clave `app_settings` al guardar o eliminar: `app/Models/AppSetting.php:19-51`.
- Para limpiar cache del framework, usar `php artisan optimize:clear` según la operación requerida en el proyecto.

## Convenciones de migraciones

- Las migraciones usan `Schema::create` y `Schema::table` con `foreignId` y `onDelete('cascade')` para relaciones críticas.
- El sistema admite ajustes de rendimiento por índices en migraciones posteriores: `database/migrations/2026_09_24_100409_add_performance_indexes_to_articulos_table.php:8-61` y `database/migrations/2026_10_15_000002_add_compound_indexes_to_ventas_and_entradas_tables.php:8-82`.
- La convención es preservar compatibilidad con re-ejecución mediante `if (!Schema::hasColumn(...))` y `if (!$this->indexExists(...))`.

## Seguridad operativa

- Las rutas y los archivos de `storage` deben mantenerse fuera del acceso público directo salvo lo que se expone con `public/storage` o `public/img`.
- La personalización de marca usa `Storage::disk('public')` y es consumida desde la configuración global. `app/Models/AppSetting.php:61-118`.
- Los mensajes de WhatsApp se centralizan en la tabla `whatsapp_pending_messages` y deben tratarse como cola de negocio real. `database/migrations/2026_05_29_125352_create_whatsapp_pending_messages_table.php:8-28`.
