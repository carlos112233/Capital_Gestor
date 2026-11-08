# 05 - Bitácora Viva de Deuda Técnica

## Registro de hallazgos

| ID | Estado | Severidad | Archivo:línea | Hallazgo |
| --- | --- | --- | --- | --- |
| DT-01 | ABIERTA | ALTA | `app/Providers/AppServiceProvider.php:43-56` | Se ejecuta una cadena de mantenimiento `config:clear`, `cache:clear`, `route:clear`, `view:clear`, `migrate --force` y `storage:link` si la request incluye `comando_secreto`; esto es un backdoor operativo y un riesgo de ejecución remota si la query string es visible o inyectable. |
| DT-02 | ABIERTA | ALTA | `resources/views/layouts/guest.blade.php:279-286` | Hay un texto hardcodeado heredado: `HAMBURGUESAS & PANADERÍA`, que no refleja el nombre de la marca del sistema ni la configuración de `AppSetting`. Es un defecto de marca y propiedad de configuración. |
| DT-03 | ABIERTA | MEDIA | `database/seeders/UsuariosSeeder.php:17-32` | Los usuarios semilla generan contraseñas `Hash::make(($index % 8) + 1)`; esto hace que las credenciales sean predecibles y no admin/rol explícito, reduciendo la ganancia de seguridad en entornos y pruebas. |
| DT-04 | ABIERTA | MEDIA | `app/Http/Controllers/ConfiguracionController.php:82-118` | Se usa `shell_exec`/`exec` para verificar y arrancar el motor WhatsApp y también se lee el sistema operativo. La integración es frágil y su política de seguridad requiere revisión profunda antes de producción. |
| DT-05 | ABIERTA | MEDIA | `database/migrations/2026_05_29_125352_create_whatsapp_pending_messages_table.php:8-28` | La cola de mensajes `whatsapp_pending_messages` se usa como bucle operacional, pero no se ve evidencia clara del consumidor final en el repositorio; su acoplamiento con el motor y el tratamiento de fallo no queda documentado en un contrato único. |
| DT-06 | EN PROGRESO | BAJA | `database/migrations/2026_09_24_100409_add_performance_indexes_to_articulos_table.php:8-61` | El proyecto ya está corrigiendo índices de rendimiento, pero los índices del negocio siguen dispersos por migraciones; la evolución del esquema requiere control de compatibilidad para no romper queries históricas. |
| DT-07 | ABIERTA | MEDIA | `app/Http/Controllers/BrandingController.php:58-90` | La lógica de uploads permite tipos y tamaños de imagen específicos, pero la política de eliminación previa, copia a `public/img` y caché de marca requiere revisión para evitar inconsistencias entre storage, public y cache. |

## Comentarios adicionales

- `DT-01` y `DT-04` requieren prioridad porque operan directamente sobre infraestructura, caché y procesos del sistema.
- `DT-02` no falla funcionalmente, pero es una deuda visible de marca y un síntoma de configuración fragmentada.
- `DT-03` afecta entorno local y pruebas; se recomienda registrarlo en la guía de despliegue y usar valores consistentes de administradores.
- `DT-05` es una deuda de observabilidad y contratabilidad del flujo de WhatsApp; si la integración cambia, la cola queda como único punto de verdad y requiere trazabilidad formal.
