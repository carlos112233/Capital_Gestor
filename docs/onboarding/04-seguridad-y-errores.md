# 04 - Trazabilidad, Seguridad y Manejo de Errores

## 1) Trazabilidad y logs

El proyecto usa `Log::info`, `Log::warning` y `Log::error` en varios servicios y controladores para monitorizar notificaciones, pagos y OCR. Se verifican varios ejemplos:

- `app/Models/Venta.php:101-114` registra `Log::info` y `Log::error` cuando se encola o falla la notificación WhatsApp.
- `app/Services/PushNotificationService.php:27-82` registra errores y resumen operativo de recordatorios push.
- `app/Services/ReceiptOcrService.php:184-324` captura errores del proceso Tesseract y fallback a Google Vision.
- `app/Http/Controllers/DashboardController.php:228` registra errores al generar PDF de estado de cuenta.

### Observación de saneamiento

- Se detectan varios `Log::error` con mensajes de excepción y contexto; sin embargo, no se observa un patrón claro de logging de contraseñas ni de tokens de autenticación en los puntos revisados. La evidencia se limita a los casos de notificación y OCR. `app/Models/Venta.php:101-114`, `app/Services/ReceiptOcrService.php:184-324`.
- ❓ NO DETERMINABLE CON EL CÓDIGO: no se puede afirmar que todo el sistema excluya credenciales de logs en todos los flujos; se requiere revisar las rutas de producción y la configuración de `logging.php` para cerrar esa auditoría.

## 2) Auditoría de seguridad de controladores

### Validación de entradas

- `app/Http/Controllers/VentaController.php:85-165` usa `request()->validate([...])` para datos de venta con reglas explícitas.
- `app/Http/Controllers/BrandingController.php:58-90` valida nombre, colores hexadecimales, `logo`, `favicon`, tamaños máximos y tipos permitidos.
- `app/Http/Controllers/ConfiguracionController.php:12-31` valida `logo` con `mimes:jpeg,png,jpg,gif,svg,webp|max:4096`.

### Prevención de asignación masiva

- `app/Models/User.php:18-38` define `fillable` con campos explícitos: `name`, `email`, `password`, `image`, `telefono`, `score_*`, `theme_*`.
- `app/Models/AppSetting.php:19-33` define `fillable` con campos de marca: `app_name`, `logo_path`, `favicon_path`, `primary_color` y demás.
- `app/Models/Role.php:7-16` también restringe mass-assignment con `protected $fillable = ['name'];`.

### Inyección SQL y consultas manuales

- Hay uso de `DB::raw` y `whereRaw` en consultas de reportes y filtros; por ejemplo `app/Models/Venta.php:57-71`, `app/Models/Feedback.php:31-42` y `app/Http/Controllers/FeedbackController.php:39-42`.
- El código usa `whereRaw` con patrones seguros y parámetros enlazados (`like ?`), no concatenaciones arbitrarias de entrada; esto reduce el riesgo de SQL injection, pero requiere revisar cada caso en producción. `app/Http/Controllers/FeedbackController.php:35-42`, `app/Models/Venta.php:57-71`.

### Permisos de archivos

- `app/Http/Controllers/BrandingController.php:58-90` acepta imágenes con tipos `jpeg,png,jpg,gif,svg,webp` y máximo 2MB; `favicon` acepta `ico,png,x-icon` y máximo 1MB.
- `app/Http/Controllers/ConfiguracionController.php:35-77` elimina versiones previas del logo y vuelve a montar la configuración de marca en `public/img`.

## 3) Puntos críticos en código

### Comando secreto expuesto vía query string

- `app/Providers/AppServiceProvider.php:43-56` ejecuta un bloque de mantenimiento si `request()->has('comando_secreto')` es verdadero; dentro del bloque vigente se ejecutan `config:clear`, `cache:clear`, `route:clear`, `view:clear`, `migrate --force` y `storage:link`.
- Este comportamiento es un punto de riesgo operativo si la URL llega a exponerse en entornos públicos o si una petición externa puede forzar la presencia de la query string.

### Inyección de esquema por entorno

- `app/Providers/AppServiceProvider.php:19-25` fuerza HTTPS solo en entorno `production`. La lógica es consistente con Laravel, pero debe mantenerse bajo control de despliegue y no depender de `APP_ENV` sin validación explícita.

### Arranque de proceso del motor Node

- `app/Http/Controllers/ConfiguracionController.php:82-118` ejecuta `pgrep` y `nohup` para iniciar `wa-motor/index.js` si no existe.
- El uso de `shell_exec` requiere revisión de seguridad y permisos del entorno; es un punto de integración caliente con el sistema operativo.

## 4) Recomendación de auditoría defensiva

- Revisar la configuración de logging real en `config/logging.php` y confirmar que no se registran nombres de usuario/telefonos con riesgo sensible en producción.
- Mantener todos los `whereRaw` con parámetros enlazados y no concatenar valores directamente.
- Reforzar la validación de uploads y la limpieza de archivos viejos en `storage/app/public/branding`.
- Proteger `comando_secreto` detrás de un secreto del entorno o un gate de usuario administrador y no dejarlo accesible en la red pública.
