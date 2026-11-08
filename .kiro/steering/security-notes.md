# Security Notes

## Reglas defensivas aplicables al taller

1. No exponer `comando_secreto` ni cualquier bypass operativo en la capa web. `app/Providers/AppServiceProvider.php:43-56`.
2. Mantener `storage/` y `public/storage` con permisos controlados y no publicar artefactos del sistema o sesión del WhatsApp en rutas web públicas. `app/Models/AppSetting.php:61-118`, `docker-compose.yml:64-88`.
3. Validar y limitar cualquier upload de imagen: tipos permitidos y tamaños máximos, con eliminación previa del archivo anterior. `app/Http/Controllers/BrandingController.php:58-90`, `app/Http/Controllers/ConfiguracionController.php:35-77`.
4. Tratar roles como control de acceso obligatorio, nunca solo como filtro visual. `app/Http/Middleware/RoleMiddleware.php:15-24`, `app/Models/User.php:310-322`.
5. Mantener `APP_URL`, `APP_ENV` y `URL::forceScheme('https')` bajo políticas de despliegue explícitas; no depender de un entorno público sin control. `app/Providers/AppServiceProvider.php:17-25`.
6. Conservar el `whatsapp_pending_messages` como cola de negocio y no como almacenamiento de un productor ad hoc sin observabilidad. `database/migrations/2026_05_29_125352_create_whatsapp_pending_messages_table.php:8-28`.

## Política de URLs locales

El proyecto tiene referencias a `http://TallerVelazque.com` como dominio local y usa `APP_URL`/`config('app.env')` para forzar esquema; por tanto, el despliegue debe mantener el dominio autorizado y no usar hosts arbitrarios de desarrollo en producción. `docker-compose.yml:1-95`, `app/Providers/AppServiceProvider.php:17-25`, `.env.example:1-34`.

## Recomendaciones clave

- Mantener `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `MAIL_*` fuera de logs y fuera de artefactos de documentación pública.
- Revisión de `shell_exec`/`exec` en `ConfiguracionController` antes de producción.
- No usar `whereRaw` sin parámetro enlazado ni concatenación de cadenas de entrada.
- Reforzar control de hotfixes con `comando_secreto` hasta que exista una autorización única y auditada.
