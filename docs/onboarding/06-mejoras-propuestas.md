# 06 - Mejoras Propuestas

## 1) Seguridad

| ID | Título | Justificación técnica | Archivos involucrados | ROI |
| --- | --- | --- | --- | --- |
| MP-01 | Desactivar `comando_secreto` y moverlo a gate administrativo real | La lógica actual permite ejecutar mantenimiento a través de una query string. Debe convertirse en un endpoint autenticado con permisos y secreto en `APP_*` o un token de entorno. | `app/Providers/AppServiceProvider.php:43-56` | Alto |
| MP-02 | Centralizar un Policy/Authorization para permisos por rol | El sistema usa comprobaciones repetidas con `hasRole()` en múltiples controladores. Un Policy centralizado reduce bypass operativo y mejora mantenibilidad. | `app/Models/User.php:310-322`, `app/Http/Middleware/RoleMiddleware.php:15-24` | Alto |
| MP-03 | Reforzar logging seguro | Debe excluir contraseñas, tokens y payloads sensibles; limitar `Log::error` a contexto necesario y sanitizar mensajes de WhatsApp y pedidos. | `app/Models/Venta.php:101-114`, `app/Services/ReceiptOcrService.php:184-324` | Medio |

## 2) Arquitectura y modelado

| ID | Título | Justificación técnica | Archivos involucrados | ROI |
| --- | --- | --- | --- | --- |
| MP-04 | Normalizar el modelo de negocio para `app_settings` y branding | Actualmente hay lógica de almacenamiento dual (`storage/app/public` + `public/img`) para mantener compatibilidad. Debe centralizarse en un único repositorio y un único contrato de URL. | `app/Models/AppSetting.php:11-118`, `app/Http/Controllers/ConfiguracionController.php:35-77`, `app/Http/Controllers/BrandingController.php:92-159` | Medio |
| MP-05 | Separar el consumidor de WhatsApp del modelo de dominio | La cola `whatsapp_pending_messages` quedó acoplada a ventas y feedback. Conviene encapsular la salida en un servicio de notificaciones con contrato y estado explícito. | `app/Models/Venta.php:60-115`, `database/migrations/2026_05_29_125352_create_whatsapp_pending_messages_table.php:8-28` | Alto |
| MP-06 | Consolidar `FormRequest` y validación en capas | Hay validación inline en varios controladores y todavía algunas consultas directas con `whereRaw`. La capa de request centraliza reglas y prueba unitaria. | `app/Http/Controllers/VentaController.php:85-165`, `app/Http/Controllers/BrandingController.php:58-90` | Medio |

## 3) Rendimiento

| ID | Título | Justificación técnica | Archivos involucrados | ROI |
| --- | --- | --- | --- | --- |
| MP-07 | Auditoría de consultas con `whereRaw` y `join` de roles | Las consultas con `join('roles', ...)` se repiten en muchos modelos. Debe revisarse la cardinalidad y el uso de `with`/`loadMissing` para evitar N+1 y joins no indexados. | `app/Models/Venta.php:57-71`, `app/Models/Feedback.php:31-42`, `app/Http/Controllers/PedidoController.php:99-110` | Medio |
| MP-08 | Trazar un contrato de índices para alta concurrencia | Los índices recientes ayudan, pero la clave es mantener una hoja de ruta de índices por tabla y por tipo de consulta (ventas por usuario/fecha, entradas por cliente/fecha). | `database/migrations/2026_09_24_100409_add_performance_indexes_to_articulos_table.php:8-61`, `database/migrations/2026_10_15_000002_add_compound_indexes_to_ventas_and_entradas_tables.php:8-82` | Medio |

## 4) DevOps y operación

| ID | Título | Justificación técnica | Archivos involucrados | ROI |
| --- | --- | --- | --- | --- |
| MP-09 | Documentar el arranque del contenedor y la dependencia de `wa-motor` | Requiere un contrato de despliegue con puertos, ubicaciones de sesión y permisos del directorio `.wwebjs_auth` y `storage`. | `docker-compose.yml:1-95`, `Dockerfile:1-56` | Alto |
| MP-10 | Crear un playbook de recuperación para `app_settings`, `storage` y WhatsApp | El sistema usa `storage/app/public`, `public/img` y `.wwebjs_auth`; un error de sincronización puede romper la marca visual y la entrega real. | `app/Models/AppSetting.php:61-118`, `app/Http/Controllers/ConfiguracionController.php:35-77`, `docker-compose.yml:64-88` | Alto |

## Priorización recomendada

1. MP-01: cerrar la ejecución por `comando_secreto`.
2. MP-05: convertir la cola de WhatsApp en servicio de notificaciones formal.
3. MP-09: documentar despliegue y coordinación de contenedores.
4. MP-04: unificar almacenamiento de branding.
5. MP-07: revisar consultas repetidas y joins de roles.

## Criterio de aceptación

- Sin `comando_secreto` expuesto como mantenimiento live en producción.
- `AppSetting` con un único punto de verdad de marca.
- Cola de WhatsApp con servicio claramente responsable y trazable.
- Índices y consultas con evidencia se revisan bajo plan de observabilidad.
