# 🚀 Capital Gestor — elbajon.store

Plataforma integral desarrollada en **Laravel 12** para la gestión de inventario, ventas, pedidos, cobranza, control de clientes, scoring crediticio, análisis de comprobantes y notificaciones automáticas en tiempo real (WebSockets & WhatsApp).

---

## 🌳 Estructura del Proyecto

```text
capital_gestor/
├── app/                        # Lógica central de la aplicación (Laravel)
│   ├── Console/                # Comandos personalizados de consola / Artisan
│   ├── Events/                 # Eventos para WebSockets y broadcasting
│   ├── Http/
│   │   ├── Controllers/        # Controladores (Artículos, Ventas, Pedidos, Clientes, etc.)
│   │   ├── Middleware/         # Middlewares (Autenticación, Roles, etc.)
│   │   └── Requests/           # Validaciones de formularios (FormRequests)
│   ├── Jobs/                   # Tareas en cola (Background jobs)
│   ├── Models/                 # Modelos Eloquent (User, Articulo, Venta, Pedido, etc.)
│   ├── Notifications/          # Notificaciones (WebPush, General, Pedidos)
│   ├── Providers/              # Service Providers de la aplicación
│   ├── Services/               # Servicios de dominio (OCR Comprobantes, Scoring, PDFs)
│   ├── Traits/                 # Traits reutilizables (ApiResponse, etc.)
│   └── View/                   # Componentes Blade
├── bootstrap/                  # Arranque del framework y caché de configuración
├── config/                     # Archivos de configuración (DB, Mail, WebSockets, etc.)
├── database/                   # Migraciones, factories y seeders de la BD
│   ├── factories/              # Generadores de datos de prueba
│   ├── migrations/             # Migraciones estructuradas de base de datos
│   └── seeders/                # Población inicial de datos y roles
├── docker/                     # Configuraciones para contenedores Docker
│   ├── nginx/                  # Servidor web Nginx para desarrollo local
│   ├── php/                    # Imagen y configuración de PHP 8.2-FPM
│   └── wa-motor/               # Dockerfile del servicio de WhatsApp
├── public/                     # Archivos estáticos accesibles públicamente
│   ├── build/                  # Assets compilados por Vite
│   ├── img/                    # Logos, iconos y recursos gráficos
│   └── sw.js                   # Service Worker para Push Notifications
├── resources/                  # Vistas, estilos y scripts fuente
│   ├── css/                    # Estilos CSS / Tailwind CSS
│   ├── js/                     # Scripts frontend (Echo, Alpine, etc.)
│   └── views/                  # Plantillas Blade (Admin, Ventas, Pedidos, PDF, etc.)
├── routes/                     # Definición de rutas del sistema
│   ├── api.php                 # Endpoints API REST
│   ├── auth.php                # Rutas de autenticación
│   ├── channels.php            # Canales privados de broadcasting (Reverb)
│   ├── console.php             # Comandos interactivos de consola
│   └── web.php                 # Rutas de la interfaz web
├── scripts/                    # Scripts de utilidad y respaldos automáticos
│   └── backup_db.sh            # Script de respaldo de MySQL
├── tests/                      # Suite de pruebas automatizadas
│   ├── Browser/                # Pruebas End-to-End con Laravel Dusk
│   ├── Feature/                # Pruebas de integración y funcionalidad
│   └── Unit/                   # Pruebas unitarias (Pest PHP)
├── wa-motor/                   # Microservicio Node.js para WhatsApp automatizado
│   ├── patches/                # Parches para dependencias
│   ├── index.js                # Punto de entrada de WhatsApp Web Bot (Puppeteer)
│   └── package.json            # Dependencias del bot
├── docker-compose.yml          # Orquestación de contenedores para entorno local
├── MANUAL_DESPLIEGUE.md        # Guía completa de despliegue en servidor / DigitalOcean
├── package.json                # Dependencias frontend (Tailwind, Vite, etc.)
├── composer.json               # Dependencias backend PHP / Laravel
└── vite.config.js              # Configuración del empaquetador Vite
```

---

## 🛠️ Stack Tecnológico

- **Backend:** Laravel 12, PHP 8.2+
- **Base de Datos:** MySQL 8.0
- **Tiempo Real:** Laravel Reverb (WebSockets)
- **Frontend:** Blade, Tailwind CSS, Vite, Alpine.js, DataTables
- **Notificaciones:** WebPush, Resend (Email), WhatsApp vía `wa-motor` (Node.js + Puppeteer)
- **Documentos & OCR:** DomPDF, Simple QrCode, Google Cloud Vision SDK
- **Testing:** Pest PHP, Laravel Dusk

---

## 🚀 Inicio Rápido con Docker (Desarrollo Local)

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/carlos112233/Capital_Gestor.git
   cd Capital_Gestor
   ```

2. **Configurar el entorno:**
   ```bash
   cp .env.docker.example .env
   ```

3. **Levantar los servicios:**
   ```bash
   docker compose up -d --build
   ```

4. **Configuración inicial de Laravel:**
   ```bash
   docker compose exec php php artisan key:generate
   docker compose exec php php artisan migrate --seed
   docker compose exec php php artisan storage:link
   ```

5. **Acceso:**
   Abre [http://localhost:8080](http://localhost:8080) en tu navegador.

Para información detallada del despliegue en producción (DigitalOcean, CI/CD, Nginx, PM2), consulta [MANUAL_DESPLIEGUE.md](file:///home/armando/capital_gestor/MANUAL_DESPLIEGUE.md).
