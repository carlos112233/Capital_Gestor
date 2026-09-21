# MANUAL MAESTRO DE DESPLIEGUE
# Capital Gestor — elbajon.store
# Última actualización: Septiembre 2026
# ──────────────────────────────────────────────────────────────────────────────

Este documento explica desde cero cómo clonar, configurar y desplegar
el proyecto Capital Gestor en cualquier computadora (local con Docker)
o en un servidor de producción nuevo (DigitalOcean).

---

## 🖥️ PARTE 1: Levantar en cualquier computadora con Docker

### Requisitos previos
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado.
- Git instalado.
- Puerto 8080 libre en tu computadora.

### Pasos (3 comandos)

```bash
# 1. Clonar el repositorio
git clone https://github.com/carlos112233/Capital_Gestor.git capital_gestor
cd capital_gestor

# 2. Copiar y configurar el archivo de entorno para Docker
cp .env.docker.example .env
# Editar .env si lo deseas (cambiar contraseñas, etc.)

# 3. Levantar todos los contenedores
docker compose up -d --build
```

### Configuración inicial (solo primera vez)

```bash
# Generar la clave de la aplicación Laravel
docker compose exec php php artisan key:generate

# Ejecutar las migraciones de la base de datos
docker compose exec php php artisan migrate --seed

# Crear enlace simbólico de almacenamiento
docker compose exec php php artisan storage:link
```

### Acceder al sistema
Abre tu navegador en: **http://localhost:8080**

### Comandos útiles del día a día

| Acción | Comando |
|---|---|
| Levantar el sistema | `docker compose up -d` |
| Detener el sistema | `docker compose down` |
| Ver los logs en tiempo real | `docker compose logs -f` |
| Entrar a la terminal de PHP | `docker compose exec php bash` |
| Entrar a la terminal de MySQL | `docker compose exec mysql mysql -u root -p` |
| Ejecutar pruebas (Pest) | `docker compose exec php vendor/bin/pest` |
| Ver el estado del motor WA | `docker compose exec wa-motor node --version` |

---

## 🌐 PARTE 2: Infraestructura del Servidor de Producción (DigitalOcean)

### Diagrama de arquitectura actual

```
Internet
    │
    ▼
Certbot (SSL Let's Encrypt)
    │
    ▼
Nginx (puerto 443/80) — /etc/nginx/sites-enabled/capital_gestor
    │           │
    │           └── /app/ y /apps/ → Puerto 8080 (Laravel Reverb WebSockets)
    │
    ▼
PHP 8.2-FPM (socket Unix: /var/run/php/php8.2-fpm.sock)
    │
    ▼
/var/www/capital_gestor (código de la aplicación)
    │
    ├── MySQL 8.0 (localhost:3306 / base de datos: gestor_capital_db)
    └── PM2 → wa-motor (Node.js + Chromium en segundo plano)
```

> **IMPORTANTE:** El servidor NO usa Docker en producción. Docker es solo para desarrollo
> local. El Droplet tiene 2 GB RAM — correr contenedores ahí causaría falta de memoria.

### Datos del servidor

| Campo | Valor |
|---|---|
| **IP del Droplet** | 206.81.14.81 |
| **Dominio** | elbajon.store |
| **OS** | Ubuntu 24.04 LTS |
| **Recursos** | 1 vCPU / 2 GB RAM / 48 GB SSD |
| **Ruta de la app** | `/var/www/capital_gestor` |
| **Usuario SSH** | `root` |
| **PHP** | 8.2-FPM via socket Unix |
| **Base de datos** | MySQL 8.0 — `gestor_capital_db` |
| **Bot WhatsApp** | PM2 proceso `wa-motor` (Node.js 20 + Chromium) |

---

## 🤖 PARTE 3: Pipeline de Despliegue Automático (GitHub Actions)

### ¿Cómo funciona?

Cada vez que haces `git push origin main`, GitHub Actions hace automáticamente:

1. ✅ Instala dependencias PHP **con** paquetes dev (para poder correr Pest).
2. ✅ Ejecuta las pruebas unitarias (`vendor/bin/pest --testsuite=Unit`).
3. Si pasan → 🚀 se conecta por SSH al Droplet y ejecuta:
   - `git pull origin main`
   - `composer install --no-dev --no-scripts` (sin scripts para evitar conflictos con caché)
   - `rm -f bootstrap/cache/packages.php bootstrap/cache/services.php` (limpieza crítica)
   - `npm ci && npm run build`
   - `php artisan migrate --force`
   - `php artisan optimize:clear && config:cache && route:cache && view:cache`
   - `pm2 reload wa-motor`

### Configuración de Secretos en GitHub (una sola vez)

Ve a tu repositorio en GitHub → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**. Añade estos 3 secretos:

| Nombre del Secreto | Valor |
|---|---|
| `DO_HOST` | `206.81.14.81` |
| `DO_USER` | `root` |
| `DO_PASSWORD` | Contraseña SSH del Droplet |

### Flujo de trabajo diario

```bash
# 1. Hacer cambios en el código
# 2. Guardar con commit
git add .
git commit -m "descripción del cambio"

# 3. Subir — el pipeline se encarga del resto automáticamente
git push origin main
```

### Verificar que el pipeline funcionó

1. Ve a tu repositorio en GitHub → pestaña **"Actions"**.
2. Verás el workflow **"🚀 Deploy a DigitalOcean (elbajon.store)"** corriendo.
3. ✅ Verde = todo desplegado correctamente en producción (~1 minuto).
4. ❌ Rojo = algo falló **antes** de llegar al servidor. La DB nunca estuvo en riesgo.

### Nota importante sobre `--no-scripts`

El `composer.json` tiene un script `post-autoload-dump` que corre `php artisan config:clear`.
Cuando se instala con `--no-dev`, Laravel Dusk se elimina de `vendor/` pero el archivo
`bootstrap/cache/packages.php` todavía lo referencia — causando un error fatal.

La solución implementada en el pipeline:
1. Usar `--no-scripts` para que Composer no ejecute ese hook automáticamente.
2. Borrar manualmente `bootstrap/cache/packages.php` y `bootstrap/cache/services.php`.
3. Correr `php artisan config:cache` después — esto regenera el caché sin Dusk.

---

## 💾 PARTE 4: Respaldos de la Base de Datos

> **Base de datos de producción:** `gestor_capital_db` en `localhost:3306`

### Hacer un respaldo manual

```bash
# Desde tu computadora (requiere acceso SSH al Droplet):
ssh root@206.81.14.81 "mysqldump -u root -p'TU_PASSWORD' gestor_capital_db | gzip" > backup_$(date +%Y%m%d).sql.gz
```

### Restaurar un respaldo

```bash
# 1. Subir el archivo al servidor
scp backup_20260921.sql.gz root@206.81.14.81:/tmp/

# 2. Restaurar en el servidor
ssh root@206.81.14.81 "gunzip < /tmp/backup_20260921.sql.gz | mysql -u root -p'TU_PASSWORD' gestor_capital_db"
```

> ⚠️ **NUNCA ejecutar `php artisan migrate:fresh` en producción** — borra TODOS los datos.
> El pipeline solo usa `php artisan migrate --force` que es seguro para datos existentes.

---

## 🔧 PARTE 5: Operaciones de Mantenimiento Comunes en Producción

### Actualizar el código manualmente (sin pipeline)

```bash
# Opción A: Script automatizado desde tu computadora local
python3 deploy_remote.py

# Opción B: Conectado al servidor por SSH directamente
cd /var/www/capital_gestor
git pull origin main
composer install --no-interaction --optimize-autoloader --no-dev --no-scripts
composer dump-autoload --no-scripts --optimize
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true
pm2 reload wa-motor || true
```

### Reiniciar el motor de WhatsApp

```bash
ssh root@206.81.14.81 "pm2 restart wa-motor"
```

### Ver el estado de todos los procesos PM2

```bash
ssh root@206.81.14.81 "pm2 list"
```

### Ver los logs del motor de WhatsApp

```bash
ssh root@206.81.14.81 "pm2 logs wa-motor --lines 50"
```

### Ver los logs de errores de Laravel

```bash
ssh root@206.81.14.81 "tail -n 100 /var/www/capital_gestor/storage/logs/laravel.log"
```

### Ver los logs de Nginx

```bash
ssh root@206.81.14.81 "tail -n 50 /var/log/nginx/capital_gestor_error.log"
```

### Limpiar caché manualmente en el servidor

```bash
ssh root@206.81.14.81 "cd /var/www/capital_gestor && php artisan optimize:clear"
```

### Verificar que el sitio responde correctamente

```bash
curl -I https://elbajon.store
# Debe responder: HTTP/2 200
```

---

## 📋 PARTE 6: Checklist para Nuevo Desarrollador

Si eres un nuevo desarrollador que se une al proyecto:

- [ ] Clonar el repositorio: `git clone https://github.com/carlos112233/Capital_Gestor.git`
- [ ] Instalar Docker Desktop
- [ ] Copiar `.env.docker.example` → `.env` y ajustar valores
- [ ] Ejecutar `docker compose up -d --build`
- [ ] Ejecutar migraciones y seeders: `docker compose exec php php artisan migrate --seed`
- [ ] Acceder en `http://localhost:8080`
- [ ] Para hacer cambios, trabajar en rama propia y hacer PR a `main`
- [ ] El merge a `main` despliega automáticamente a producción
