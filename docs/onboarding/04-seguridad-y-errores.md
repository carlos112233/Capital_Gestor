# 04 — Trazabilidad, Seguridad y Manejo de Errores

> Convenciones: **[HECHO]** · **[INFERENCIA]** · **[DUDA]** · `❓ NO DETERMINABLE CON EL CÓDIGO`.
> Ningún secreto se reproduce en este documento: se citan solo nombres de variables y ubicaciones.

---

## 1. Trazabilidad y logs

- **[HECHO]** Canal por defecto `env('LOG_CHANNEL', 'stack')` (`config/logging.php:21`); canal `daily` con retención `LOG_DAILY_DAYS` = 14 (`config/logging.php:68-72`).
- **[HECHO]** 65 llamadas a `Log::*` en `app/` (conteo por `grep`). Ejemplo: `Log::warning` por comprobante que no pasa validación OCR (`app/Http/Controllers/ComprobanteController.php:44`).
- **[HECHO]** No se encontraron llamadas `Log::*` que incluyan `password`, `token` o `clave` en `app/`.
- ⚠️ **[HECHO]** Contraseñas en **texto plano** se encolan en `whatsapp_pending_messages.mensaje` ("🔑 Contraseña: …") al crear/editar/reenviar accesos de cliente: `app/Http/Controllers/ClienteController.php:63,84,133,148,163-173,178-199`. Persisten en BD indefinidamente.
- ⚠️ **[HECHO]** `GET /descargar-log-secreto` descarga `storage/logs/laravel.log` **sin autenticación** (`routes/web.php:142-150`). Con `APP_DEBUG=true` (`.env.example:4`) el log contiene trazas, SQL y datos de petición.
- **[HECHO]** `wa-motor` registra en consola el host y la base de datos de conexión (`wa-motor/index.js:88`).
- `bootstrap/app.php:45-47`: `withExceptions` vacío → manejo de errores por defecto de Laravel.

---

## 2. Auditoría de controladores

### 2.1 Validación de entradas
- **[HECHO]** Solo existen 2 `FormRequest`: `Auth/LoginRequest` (no usado por el login web, ver `03-flujos-negocio.md` §1.1) y `ProfileUpdateRequest` (`app/Http/Requests/ProfileUpdateRequest.php:29`).
- **[HECHO]** El resto valida inline con `$request->validate()` (ej. `VentaController.php:98`, `PagoVendedorController.php:186-194`, `BrandingController.php:76-83`, `ClienteController.php:55-60`).
- ⚠️ **[HECHO]** Precio de venta controlado por el cliente (`CatalogoController.php:53`, `VentaController.php:98`, `Api/CatalogoApiController.php:52`).

### 2.2 Asignación masiva (`$fillable` / `$guarded`)

| Modelo | Protección | Observación | Evidencia |
|---|---|---|---|
| `User` | `$fillable` | Incluye `score_*`, `override_score`, `theme_*`; sin `roles` | `app/Models/User.php:30-43` |
| `Role` | `$fillable = ['name']` | — | `app/Models/Role.php:12` |
| `Articulo` | `$fillable` | Incluye `img_tipo` inexistente | `app/Models/Articulo.php:16-25` |
| `Venta` | `$fillable` | Incluye `cliente_id` inexistente | `app/Models/Venta.php:13-22` |
| `Pedido` | `$fillable` | Incluye `status`, `venta_id` | `app/Models/Pedido.php:9-17` |
| `Entrada` | `$fillable` | Incluye `registrado_por_id` | `app/Models/Entrada.php:13-21` |
| `Comprobante` | `$fillable` | Incluye `status` | `app/Models/Comprobante.php:9-21` |
| `PagoVendedor` | `$fillable` | — | `app/Models/PagoVendedor.php:27` |
| `AppSetting` | `$fillable` | — | `app/Models/AppSetting.php:22` |
| `Feedback`, `FeedbackMensaje`, `UserTutorial`, `Cliente` | `$fillable` | — | `app/Models/Feedback.php:18`, `FeedbackMensaje.php:15`, `UserTutorial.php:9`, `Cliente.php:20` |

- **[HECHO]** Ningún modelo usa `$guarded = []`.
- **[INFERENCIA]** Si un controlador hiciera `Model::create($request->all())`, campos como `Comprobante.status` o `User.override_score` serían asignables. Búsqueda exhaustiva de `->all()` en controladores: pendiente (`❓`).

### 2.3 SQL crudo
- **[HECHO]** Todos los `whereRaw` usan bindings `?` (ej. `CatalogoController.php:25`, `VentaController.php:45`, `CobroController.php:41`, `NotificationCircuitBreakerService.php:52,60`). Sin concatenación de entrada de usuario detectada.
- **[HECHO]** `DB::raw('LOWER(roles.name)')` con valores literales (`Venta.php:58,127`, `PedidoController.php:100,111`).
- **[HECHO]** `DB::statement` con interpolación solo de nombres internos: `FixPostgresSequences.php:41`, `MigrateBase64Storage.php:460`.
- ⚠️ **[HECHO]** Operador PostgreSQL `ILIKE` en la API, incompatible con MySQL 8 (error de sintaxis): `Api/VentaApiController.php:42`, `Api/EntradaApiController.php:45`, `Api/ClienteApiController.php:30-32,158,176-177`.
- **[HECHO]** `CobroController.php:248` usa `DATE_FORMAT` (solo MySQL) → el código mezcla dialectos.

### 2.4 Subida de archivos

| Endpoint | Regla | Riesgo | Evidencia |
|---|---|---|---|
| Branding logo | `image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048` | **SVG** (XSS almacenado) | `BrandingController.php:82` |
| Branding favicon | `file|mimes:ico,png,x-icon|max:1024` | — | `BrandingController.php:83` |
| Configuración logo | `image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096` | **SVG** copiado a `public/img/Logo.svg` | `ConfiguracionController.php:27,56-61` |
| Comprobante | `image|mimes:jpeg,png,jpg,webp|max:5120` | Disco **público** con datos bancarios | `ComprobanteController.php:24,29` |
| Feedback | `image|mimes:jpeg,png,jpg,webp|max:5120` | Escribe en `public/uploads` | `FeedbackController.php:83,86-92,175` |
| Artículo | `image|mimes:jpg,jpeg,png|max:2048` | — | `ArticuloController.php:50,79` |
| Avatar cliente | `image|mimes:jpg,jpeg,png|max:2048` | — | `ClienteController.php:60,114` |
| Perfil | `image|mimes:jpg,jpeg,png|max:2048` | — | `ProfileUpdateRequest.php:29` |

- **[HECHO]** Nginx bloquea ejecución de `.php` en `/uploads|storage|img/` (`docker/nginx/default.conf:17-20`) y límite de cuerpo 50M (`docker/nginx/default.conf:11`; PHP `upload_max_filesize=50M` en `docker/php/local.ini:8`).
- **[HECHO]** `SecurityHeadersMiddleware` añade `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` y CSP (`app/Http/Middleware/SecurityHeadersMiddleware.php:21-34`).

### 2.5 Autorización
- **[HECHO]** Solo `role:admin` se aplica a nivel de rutas (`routes/web.php:26,61`; `routes/api.php:38`). No hay Policies (`app/Policies` no existe).
- **[HECHO]** Rutas de negocio con solo `auth`: `catalogo/vender`, `resource pedidos`, `resource ventas` (`routes/web.php:46-50`). Las validaciones de propiedad (que un cliente no edite ventas ajenas) dependen de cada controlador: `❓ NO DETERMINABLE CON EL CÓDIGO` sin pruebas de autorización.
- **[HECHO]** `/push/*` excluido de CSRF (`bootstrap/app.php:30-32`).
- **[HECHO]** `trustProxies(at: '*')` (`bootstrap/app.php:27`).

---

## 3. Puntos críticos en código (bypass / depuración / secretos)

| # | Hallazgo | Evidencia |
|---|---|---|
| 1 | Backdoor `?comando_secreto` sin auth: limpia cachés, `migrate --force`, `storage:link`, `die()` | `app/Providers/AppServiceProvider.php:43-63` |
| 2 | Descarga pública del log | `routes/web.php:142-150` |
| 3 | Rutas de prueba públicas (broadcast y ntfy.sh) | `routes/web.php:152-169` |
| 4 | `.env.example` versionado con `APP_KEY`, `DB_PASSWORD`, `MAIL_USERNAME` (Gmail) y `MAIL_PASSWORD` **con valores reales no vacíos** | `.env.example:3,28,54,55` |
| 5 | `.env.txt` versionado en git (copia de entorno con `APP_KEY`, `DB_PASSWORD`, …) | `git ls-files` → `.env.txt` |
| 6 | Volcados SQL versionados (~100 MB) con `INSERT INTO users` (63 coincidencias) — datos personales y hashes | `render_db_data_inserts.sql`, `render_db_backup.sql`, `estructura-gestor_capital_db-202509021427.sql` |
| 7 | Contraseña PostgreSQL de respaldo hardcodeada | `wa-motor/index.js:44,49` |
| 8 | Contraseña SSH de `root` enviada literal vía `pexpect`; `StrictHostKeyChecking=no`; IP pública del servidor | `deploy_remote.py:8-12` |
| 9 | Cuentas bancarias (BBVA / Mercado Pago, CLABE) hardcodeadas en mensajes | `app/Http/Controllers/DashboardController.php:234,312-316`; `app/Console/Commands/EnviarRecordatoriosMasivos.php:45` |
| 10 | Teléfonos de fallback hardcodeados | `app/Models/Venta.php:73,142`; `app/Services/PdfReceiptService.php:183` |
| 11 | Deuda ficticia `4250.00` en PDF | `app/Services/PdfReceiptService.php:149-151` |
| 12 | Ejecución de procesos del SO desde HTTP (`shell_exec`, `exec`, `pkill -9`) | `app/Http/Controllers/ConfiguracionController.php:131-134,176,219-220` |
| 13 | `chmod -R 777` en arranque de producción (Render) | `docker-start.sh:11` |
| 14 | Ruta local del desarrollador para el QR | `wa-motor/index.js:371,404` |
| 15 | Scripts *scratch* versionados en raíz | `scratch_fix_admin_check.py`, `scratch_restore_guest.php`, `scratch_test.php`, `scratch_update_controller.py` |

> [!CAUTION]
> Los hallazgos 4–8 implican que credenciales y datos personales ya están en el historial de git. Rotarlos es obligatorio **aunque** se borren los archivos; el historial los conserva.
