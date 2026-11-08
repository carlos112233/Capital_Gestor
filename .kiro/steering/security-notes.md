# Security Notes Steering — Capital Gestor (Taller Velázquez)

> Directrices de seguridad operativa, defensiva y hardening de infraestructura · Última actualización: 2026-11-07
> Convenciones: **[HECHO]** · **[INFERENCIA]** · **[DUDA]** · `❓ NO DETERMINABLE CON EL CÓDIGO`.

---

## 1. Protección del Sistema de Archivos (`storage/` y `uploads/`)

1. **Restricción de Ejecución PHP:**
   - **[HECHO]** Nginx bloquea tajantemente la ejecución de scripts `.php` dentro de carpetas de subida de usuarios (`docker/nginx/default.conf:17-20`):
     ```nginx
     location ~* ^/(uploads|storage|img)/.*\.php$ {
         deny all;
         return 404;
     }
     ```
2. **Validación de Tipos MIME y Sanitización:**
   - Toda subida de imagen debe validar extensiones estrictas (`jpg,jpeg,png,webp`).
   - ⚠️ **Riesgo Detectado:** La inclusión de `svg` en `BrandingController.php:82` y `ConfiguracionController.php:27` permite ataques de Stored XSS mediante inyección de etiquetas `<script>` o `onload` en el vector SVG. **Directriz:** Eliminar `svg` de los mimes admitidos o sanitizar con DOMPurify/Enlightn antes de almacenarlo.
3. **Privacidad de Comprobantes Bancarios:**
   - Actualmente los tickets bancarios se almacenan en el disco público `storage/app/public/comprobantes` (`app/Http/Controllers/ComprobanteController.php:29`). Deben migrarse al disco `local` privado y servirse mediante controladores autenticados con `Storage::download()`.

---

## 2. Control de Acceso y Blindaje de Roles

1. **Principio de Mínimo Privilegio en Rutas:**
   - Toda ruta que afecte configuraciones globales, balance de vendedores o auditorías debe estar protegida explícitamente con `middleware(['auth', 'role:admin'])`.
2. **Throttling en Endpoints de Autenticación:**
   - La API implementa `throttle:6,1` en `/api/login` (`routes/api.php:16`).
   - El login web en `AuthenticatedSessionController.php:25-62` DEBE migrar al uso estricto de `App\Http\Requests\Auth\LoginRequest` para evitar ataques de fuerza bruta al panel web.

---

## 3. Política de URLs y Prevención de Fugas de Información

1. **Eliminación Inmediata de Backdoors y Logs Públicos:**
   - ⚠️ Desmantelar el parámetro `comando_secreto` de `app/Providers/AppServiceProvider.php:43-63`. Toda operación de mantenimiento debe ejecutarse vía CLI (`php artisan`).
   - ⚠️ Eliminar la ruta pública `GET /descargar-log-secreto` (`routes/web.php:142-150`).
2. **Depuración de Secretos en Repositorio:**
   - Prohibido versionar volcados `.sql` con datos personales (`render_db_data_inserts.sql`), archivos `.env.txt` o scripts con credenciales SSH en texto plano (`deploy_remote.py`).
   - Rotar inmediatamente las llaves expuestas en `.env.example` (`APP_KEY`, contraseña SMTP de Gmail).
