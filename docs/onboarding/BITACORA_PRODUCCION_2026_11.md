# 07 — Bitácora de Hito de Salida a Producción y Roadmap Multi-Empresa (`elbajon.store`)

> **Fecha de Hito:** 20 de Noviembre de 2026  
> **Dominio Productivo:** `https://elbajon.store`  
> **Estado Operativo:** `EN PRODUCCIÓN (100% ESTABLE)`  
> **Convenciones:** **[HECHO]** · **[VERIFICADO EN VIVO]** · **[ROADMAP]**

---

## 1. Infraestructura Activa en Producción (`https://elbajon.store`)

| Capa / Componente | Especificación Productiva | Estado |
| :--- | :--- | :--- |
| **Sistema Operativo** | Ubuntu 24.04 LTS (DigitalOcean Droplet — `/var/www/capital_gestor`) | **[HECHO]** |
| **Servidor Web & TLS** | Nginx `1.24.0` con terminación SSL/TLS y cabecera `Strict-Transport-Security: max-age=31536000; includeSubDomains` + `X-Frame-Options: SAMEORIGIN` + `X-Content-Type-Options: nosniff` + `Content-Security-Policy` | **[VERIFICADO EN VIVO]** |
| **Runtime PHP** | PHP `8.2-FPM` con extensiones `php8.2-redis`, `php8.2-mbstring`, `php8.2-mysql`, `php8.2-gd`, `php8.2-xml` | **[VERIFICADO EN VIVO]** |
| **Base de Datos Relacional** | MySQL `8.0` con respaldos automáticos `mysqldump` pre-migración en `/var/backups/capital_gestor/pre_deploy_*.sql.gz` e índices compuestos B-Tree de alto rendimiento | **[VERIFICADO EN VIVO]** |
| **In-Memory Data Store** | Redis `7+` (`127.0.0.1:6379`, cliente `phpredis`) gestionando `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis` y contadores de rate-limiting | **[VERIFICADO EN VIVO]** |
| **Worker de Colas Laravel** | Servicio `systemd` dedicado (`/etc/systemd/system/capital-queue.service`) ejecutando `php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --memory=128` bajo `www-data` | **[VERIFICADO EN VIVO]** |
| **Motor WhatsApp (`wa-motor`)** | Daemon Node.js gestionado por PM2 (`--max-memory-restart 350M`), corriendo `whatsapp-web.js@1.34.8` nativo sin parches locales y Chromium headless | **[VERIFICADO EN VIVO]** |
| **Pipeline CI/CD** | GitHub Actions (`.github/workflows/deploy.yml`): suite completa Pest sobre SQLite `:memory:` en runner CI + despliegue SSH automatizado en `origin/main` | **[VERIFICADO EN VIVO]** |

---

## 2. Seguridad, Auditoría OWASP Top 10 y Sanidad de Dependencias

1. **Cierre del 100% del Catálogo de Deuda Técnica (`DT-01` a `DT-19` en `05-deuda-tecnica.md`):**
   - **[HECHO]** Eliminación de rutas públicas de prueba (`/enviar-alerta`, `/test-notif` → retornan `HTTP 404` en producción) y rutas de depuración legacy (`/descargar-log-secreto`, `?comando_secreto`).
   - **[HECHO]** Sustitución de contraseñas temporales en texto plano por tokens criptográficos de un solo uso (`Password::broker()->createToken($user)`) con vigencia de 15 minutos y rate-limiting `throttle:3,15`.
   - **[HECHO]** Rate-limiting compuesto `email|ip` en login web (`LoginRequest::authenticate()`) con disparo de evento `Lockout` al 6.º intento fallido.
   - **[HECHO]** Almacenamiento privado de comprobantes bancarios en `storage/app/private/comprobantes` servido mediante endpoint autenticado `/comprobantes/{id}/imagen` con control estricto de propietario/admin (`HTTP 403` ante accesos cruzados).
   - **[HECHO]** Bloqueo de vectores `.svg` en subidas de logotipos/favicons para prevenir Stored XSS, y blindaje contra Mass Assignment en `User` (`score_*`, `override_score` excluidos de `$fillable`) e IDOR en `ClienteApiController` / `EntradaApiController`.
2. **Auditoría Cero Vulnerabilidades en Dependencias (`composer audit` & `npm audit`):**
   - **[HECHO]** `composer audit`: **0 advisories** tras actualización menor controlada de `laravel/framework` y componentes `symfony/*` (`c1333a7`).
   - **[HECHO]** `npm audit` en raíz (`/`): **0 vulnerabilities** (`57c5d76`).
   - **[HECHO]** `npm audit` en `wa-motor/`: **0 vulnerabilities** mediante actualización exacta a `whatsapp-web.js@1.34.8` (que incorpora upstream el fix `delete message.__x_id` en `src/util/Injected/Utils.js`, permitiendo retirar `patch-package` y `patches/whatsapp-web.js+1.34.7.patch` en `58727ad`) y bloque `"overrides"` seguro (`@puppeteer/browsers@^3.2.7`, `basic-ftp@^6.2.6`, `glob@^14.0.6`, `minimatch@^11.0.9`, `zod@^4.6.7`).
3. **Reparación de Binding Posicional SQL en `wa-motor/index.js` (`290b321`):**
   - **[HECHO]** Corregido el traductor `queryDb(sql, params)` para mapear explícitamente cada marcador `$N` a `params[N - 1]` al convertir a `?` en MySQL, alineando además el orden secuencial `$1, $2, $3` y truncando `error_message` a 500 caracteres. Esto erradicó el error `Truncated incorrect DOUBLE value: 'No LID for user...'` y evita reintentos infinitos en números sin cuenta de WhatsApp.

---

## 3. Calidad, Arquitectura de Dominio y Cobertura de Pruebas

- **[HECHO]** **Desacoplamiento POS y Cocina:** Extracción de `RegistrarVentaAction` y `RegistrarPedidoAction` con transacciones ACID, bloqueo pesimista `lockForUpdate()`, validación exclusiva contra `articulos.precio` en BD y metadatos estructurados (`requiere_cocina`, `categoria_cocina`), eliminando heurísticas `str_contains`.
- **[HECHO]** **Unificación Financiera FIFO y Nómina en MySQL:** Extracción de `EstadoCuentaFifoService` (compartido entre `CobroController` y `DashboardController`), eliminación de consultas N+1 en `ClientScoringController@getAnalyticsData`, y migración de retenciones de vendedores a MySQL (`users.porcentaje_retencion`, `app_settings.porcentaje_retencion_default`).
- **[HECHO]** **Suite Automatizada Pest (100% Verde en MySQL 8.0 y SQLite `:memory:`):**
  - **91 tests aprobados (454 assertions, 0 fallos).**
  - Compatibilidad dual garantizada en todas las migraciones (`MigrateBase64Storage` aislado por driver en `99ebdd6` y restricción `CHECK` de `comprobantes.status` homologada para SQLite CI en `4609831`).
- **[VERIFICADO EN VIVO]** **Smoke Tests Productivos:**
  - `GET https://elbajon.store` → `HTTP 302` hacia `https://elbajon.store/login` con HSTS y cabeceras de seguridad activas.
  - `GET https://elbajon.store/enviar-alerta` y `/test-notif` → `HTTP 404 Not Found`.
  - `GET https://elbajon.store/api/catalogo` → `HTTP 401 Unauthorized` sin token y respuesta JSON con `requiere_cocina` y `categoria_cocina` bajo `auth:sanctum`.

---

## 4. Roadmap de Siguiente Fase: Arquitectura Multi-Empresa (Single-DB Multi-Tenancy)

> **[ROADMAP]** Diseño aprobado para evolución a modelo Multi-Empresa (*Single-Database Multi-Tenancy*) con migración *zero-downtime*, estimado en **2 a 3 horas de ejecución total** divididas en 4 fases atómicas:

| Fase | Nombre | Alcance Técnico Principal | Estimación |
| :--- | :--- | :--- | :--- |
| **Fase 1** | **Cimientos de Base de Datos y Backfill Zero-Downtime** | Crear tablas `empresas` (`id`, `nombre`, `slug`, `activo`, `configuracion_json`, `timestamps`) y pivote `empresa_user` (`empresa_id`, `user_id`, `rol_empresa`, `es_predeterminada`). Añadir columna `empresa_id` (inicialmente nullable, indexada con llave foránea) en tablas operativas (`articulos`, `ventas`, `pedidos`, `entradas`, `comprobantes`, `pagos_vendedores`, `feedbacks`, `app_settings`, `whatsapp_pending_messages`). Ejecutar seeder/migración idempotente que cree la **Empresa #1 ("El Bajón")** y asigne `empresa_id = 1` a todos los registros históricos antes de activar `NOT NULL`. | ~45 min |
| **Fase 2** | **Aislamiento Automático en Dominio (`BelongsToEmpresa` + `EmpresaScope`)** | Implementar el trait `BelongsToEmpresa` y el `GlobalScope` `EmpresaScope` que filtre transparentemente toda consulta Eloquent por la empresa activa en sesión/token (`session('empresa_activa_id')` o cabecera/atributo en API Sanctum), e inyecte automáticamente `empresa_id` en el evento `creating` de cada modelo operativo. | ~45 min |
| **Fase 3** | **Contexto HTTP, Selector UI y Aislamiento en Servicios/Actions** | Crear middleware `EnsureEmpresaContext` para Web y API, selector rápido de empresa en la barra superior (`navigation.blade.php`) para usuarios multi-empresa, y propagar `empresa_id` en `RegistrarVentaAction`, `RegistrarPedidoAction`, `EstadoCuentaFifoService`, `WhatsAppNotificationService` y `wa-motor`. | ~45 min |
| **Fase 4** | **Suite Pest de Aislamiento Cross-Tenant (Anti-Leak)** | Agregar `tests/Feature/MultiTenantIsolationTest.php` verificando matemáticamente que un usuario o administrador operando en la **Empresa A** jamás pueda leer, vender, cobrar ni aprobar comprobantes de la **Empresa B**, manteniendo los 91 tests existentes en verde. | ~30 min |
