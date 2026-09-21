# MANUAL MAESTRO DE DESPLIEGUE
# Capital Gestor — elbajon.store
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
| Ver el estado del motor WA | `docker compose exec wa-motor pm2 list` |

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
    │           └── /app/ y /apps/ → Puerto 8080 (Laravel Reverb)
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

### Datos del servidor

| Campo | Valor |
|---|---|
| **IP del Droplet** | 206.81.14.81 |
| **Dominio** | elbajon.store |
| **OS** | Ubuntu 24.04 LTS |
| **Recursos** | 1 vCPU / 2 GB RAM / 48 GB SSD |
| **Ruta de la app** | `/var/www/capital_gestor` |
| **Usuario SSH** | `root` |

---

## 🤖 PARTE 3: Pipeline de Despliegue Automático (GitHub Actions)

### ¿Cómo funciona?

Cada vez que haces `git push origin main`, el robot de GitHub Actions hace automáticamente:

1. ✅ Instala dependencias y ejecuta las pruebas unitarias.
2. Si pasan: 🚀 se conecta por SSH a tu Droplet.
3. Ejecuta `git pull`, `composer install`, `npm run build`, migraciones y limpia la caché.
4. Recarga el motor de WhatsApp con PM2.

### Configuración de Secretos en GitHub (una sola vez)

Ve a tu repositorio en GitHub → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**. Añade:

| Nombre del Secreto | Valor |
|---|---|
| `DO_HOST` | `206.81.14.81` (o `elbajon.store`) |
| `DO_USER` | `root` |
| `DO_PASSWORD` | Tu contraseña SSH del Droplet |

### Verificar que el pipeline funcionó

1. Ve a tu repositorio en GitHub.
2. Haz clic en la pestaña **"Actions"**.
3. Verás el workflow **"🚀 Deploy a DigitalOcean"** corriendo.
4. Si hay una palomita verde ✅ = todo bien en producción.
5. Si hay una X roja ❌ = algo falló ANTES de llegar al servidor (tu DB en producción nunca estuvo en riesgo).

---

## 💾 PARTE 4: Respaldos de la Base de Datos

### Hacer un respaldo manual

```bash
# Desde tu computadora (requiere acceso SSH al Droplet):
ssh root@206.81.14.81 "mysqldump -u root -p'TU_PASSWORD' gestor_capital_db | gzip" > backup_$(date +%Y%m%d).sql.gz
```

### Restaurar un respaldo

```bash
# Subir el archivo al servidor
scp backup_20260921.sql.gz root@206.81.14.81:/tmp/

# Restaurar en el servidor
ssh root@206.81.14.81 "gunzip < /tmp/backup_20260921.sql.gz | mysql -u root -p'TU_PASSWORD' gestor_capital_db"
```

---

## 🔧 PARTE 5: Operaciones de Mantenimiento Comunes en Producción

### Actualizar el código manualmente

```bash
# Opción A: Desde tu computadora local (recomendado)
python3 deploy_remote.py

# Opción B: Conectado al servidor por SSH
cd /var/www/capital_gestor
git pull origin main
composer install --no-interaction --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
pm2 reload wa-motor
```

### Reiniciar el motor de WhatsApp

```bash
ssh root@206.81.14.81 "pm2 restart wa-motor"
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

