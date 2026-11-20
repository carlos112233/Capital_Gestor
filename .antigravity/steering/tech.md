# Tech Steering — Capital Gestor (Taller Velázquez)

> Gobernanza técnica, infraestructura y directrices de desarrollo · Última actualización: 2026-11-07
> Convenciones: **[HECHO]** · **[INFERENCIA]** · **[DUDA]** · `❓ NO DETERMINABLE CON EL CÓDIGO`.

---

## 1. Stack Tecnológico Consolidado

- **Runtime:** PHP 8.2+ (`composer.json:9`), contenedor local bajo Alpine (`docker/php/Dockerfile:4`).
- **Framework:** Laravel 12 (`"laravel/framework": "^12.0"` en `composer.json:13`).
- **Base de Datos:** MySQL 8.0 en contenedor Docker (`docker-compose.yml:56`). Soporte heredado para PostgreSQL vía `pdo_pgsql` (`Dockerfile:17`).
- **Servidor Web:** Nginx 1.25 Alpine actuando como reverse proxy hacia PHP-FPM (`docker-compose.yml:6-18`).
- **Frontend:** Blade Views con Alpine.js v3.4.2, Tailwind CSS v3.1.0 y Vite v7.0.4 (`package.json:11-19`). Sin Livewire.
- **Microservicio WhatsApp:** Node.js v20 Slim con `whatsapp-web.js` y Chromium (`docker/wa-motor/Dockerfile:5-10`, `wa-motor/index.js:1-740`).

---

## 2. Topología y Comandos de Despliegue Docker

### 2.1 Topología Multi-Contenedor (`docker-compose.yml`)

1. `capital_gestor_nginx` (`nginx:1.25-alpine`): Expone puerto host `8080` a puerto `80` interno (`docker-compose.yml:10-11`).
2. `capital_gestor_php` (`php:8.2-fpm-alpine`): Conexión FastCGI en puerto interno `9000` (`docker-compose.yml:23-49`, `docker/php/Dockerfile:70`).
3. `capital_gestor_mysql` (`mysql:8.0`): Puerto host expuesto en `3307:3306` para evitar colisiones locales, con volumen persistente `mysql_data` (`docker-compose.yml:55-74`).
4. `capital_gestor_wa_motor` (`node:20-slim`): Daemon de mensajería con volumen persistente para sesión `./.wwebjs_auth` y bind `./storage` para lectura de PDFs (`docker-compose.yml:79-100`).

### 2.2 Secuencia Oficial de Arranque

```bash
# 1. Preparar archivo de variables para docker
cp .env.docker.example .env

# 2. Levantar el stack completo en segundo plano
docker compose up -d --build

# 3. Inicialización de Laravel (única vez)
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --force
docker compose exec php php artisan db:seed
docker compose exec php php artisan storage:link
```

> [!NOTE]
> Para entornos Windows, si no se cuenta con `iniciar_windows.bat`, se ejecutan exactamente los mismos comandos anteriores desde PowerShell o Git Bash.

---

## 3. Reglas de Optimización y Caché

En entornos de producción (o tras cambios en variables de entorno), se debe ejecutar:

```bash
# Limpiar y regenerar cachés de alto rendimiento
php artisan optimize:clear
php artisan optimize
```

- **[HECHO]** El helper `AppSetting::clearSettingsCache()` (`app/Models/AppSetting.php:47-53`) invalida automáticamente la caché de interfaz tras guardar modificaciones en `/admin/personalizacion`.
- **[HECHO]** Opcache está configurado con revalidación activa en `docker/php/local.ini:23-29` para desarrollo continuo sin reinicio forzado del contenedor.

---

## 4. Convenciones de Base de Datos y Migraciones

1. **Campos Monetarios:** Todo valor financiero DEBE declararse como `$table->decimal('campo', 10, 2)`. Prohibido el uso de `float` o `double` en esquemas de base de datos.
2. **Llaves Foráneas y Eliminación:** 
   - Tablas maestras o críticas (`articulos`, `roles`) usan restricción `restrictOnDelete` o default restrict.
   - Dependencias de usuario secundarias usan `cascadeOnDelete` (`comprobantes`, `feedbacks`, `role_user`).
   - Auditorías y compras conservan historial con `nullOnDelete` (`ventas.comprador_id`, `entradas.registrado_por_id`).
3. **Indexación Requerida:** Todo campo de búsqueda frecuente (`created_at`, `status`, `user_id`, `numero`) debe declarar índice B-Tree individual o compuesto cubierto.
