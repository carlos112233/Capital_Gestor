# 05 - Bitácora Viva de Deuda Técnica

## Registro de hallazgos

| ID | Estado | Severidad | Archivo:línea | Hallazgo |
| --- | --- | --- | --- | --- |
| DT-01 | RESUELTA | ALTA | `app/Providers/AppServiceProvider.php:43-56` | Se eliminó la ejecución de mantenimiento desde una request web con `comando_secreto`; la operación queda formalizada en un comando Artisan (`php artisan app:mantenimiento-cache`) para evitar backdoors operativos. |
| DT-02 | RESUELTA | ALTA | `resources/views/layouts/guest.blade.php:279-286` | Se reemplazó el texto hardcodeado por una lectura dinámica desde `$appSettings->tagline ?? $appSettings->nombre_empresa ?? 'SERVICIO AUTOMOTRIZ'`, alineando la marca con la configuración del sistema. |
| DT-03 | RESUELTA | MEDIA | `database/seeders/UsuariosSeeder.php:17-32` | Se reemplazó la generación aleatoria y predecible de contraseñas por cuentas deterministas parametrizadas por entorno (`ADMIN_DEFAULT_*`, `VENDEDOR_DEFAULT_*`, `COCINA_DEFAULT_*`, `CLIENTE_DEFAULT_*`) y se volvió el seeder idempotente con `updateOrCreate` y `syncWithoutDetaching`. |
| DT-04 | RESUELTA | MEDIA | `app/Http/Controllers/ConfiguracionController.php:82-118` | Se eliminó el uso de `shell_exec`/`exec` para arrancar y verificar `wa-motor`, y se reemplazó por validación defensiva de rutas y el Facade `Illuminate\Support\Facades\Process` con timeouts controlados y manejo estructurado de errores. |
| DT-05 | ABIERTA | MEDIA | `database/migrations/2026_05_29_125352_create_whatsapp_pending_messages_table.php:8-28` | La cola de mensajes `whatsapp_pending_messages` se usa como bucle operacional, pero no se ve evidencia clara del consumidor final en el repositorio; su acoplamiento con el motor y el tratamiento de fallo no queda documentado en un contrato único. |
| DT-06 | EN PROGRESO | BAJA | `database/migrations/2026_09_24_100409_add_performance_indexes_to_articulos_table.php:8-61` | El proyecto ya está corrigiendo índices de rendimiento, pero los índices del negocio siguen dispersos por migraciones; la evolución del esquema requiere control de compatibilidad para no romper queries históricas. |
| DT-07 | ABIERTA | MEDIA | `app/Http/Controllers/BrandingController.php:58-90` | La lógica de uploads permite tipos y tamaños de imagen específicos, pero la política de eliminación previa, copia a `public/img` y caché de marca requiere revisión para evitar inconsistencias entre storage, public y cache. |

## Comentarios adicionales

- `DT-01` y `DT-04` requieren prioridad porque operan directamente sobre infraestructura, caché y procesos del sistema.
- `DT-02` no falla funcionalmente, pero es una deuda visible de marca y un síntoma de configuración fragmentada.
- `DT-03` afecta entorno local y pruebas; se recomienda registrarlo en la guía de despliegue y usar valores consistentes de administradores.
- `DT-05` es una deuda de observabilidad y contratabilidad del flujo de WhatsApp; si la integración cambia, la cola queda como único punto de verdad y requiere trazabilidad formal.
