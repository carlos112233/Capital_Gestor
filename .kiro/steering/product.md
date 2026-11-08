# Product Steering

## Resumen del sistema

El Taller Velázquez (Capital Gestor) es una plataforma operativa y financiera para un taller automotriz y de servicios con módulos de ventas, inventario, entradas de capital, administración de usuarios y personalización visual. El código refleja un sistema con foco en operación, relaciones de cliente/usuario, contabilidad y notificaciones inmediatas por WhatsApp. `routes/web.php:1-170`, `routes/api.php:1-42`.

## Roles del taller

- Administrador: acceso a paneles administrativos, gestión de clientes, artículos, entradas, pagos y personalización. `routes/web.php:49-104`.
- Vendedor: acceso a ventas, liquidados y consultas relevantes del negocio. `app/Http/Controllers/VentaController.php:17-45`.
- Cliente: acceso a su propio estado, compras y consumo de servicios. `app/Models/User.php:310-322` y `routes/web.php:26-46`.
- Cocina / Operación: rol que se crea en `database/seeders/RoleSeeder.php:15-18`; se usa en validaciones y exclusiones de usuarios. 

## Catálogo de servicios del sistema

1. Venta y cobro de artículos/servicios.
2. Gestión de pedidos y flujo de producción/entrega.
3. Registro de entradas de capital y saldos.
4. Gestión de clientes, usuarios y roles.
5. Panel de configuración y branding.
6. Envío masivo y puntual por WhatsApp.
7. Generación de comprobantes y estado de cuenta con PDFs.
8. push notifications y recordatorios automáticos. `routes/console.php:1-39`.

## Mapa de pantallas del panel administrativo

- `GET /admin/dashboard` -> Dashboard administrativo con panel de métricas. `routes/web.php:26-46`.
- `GET /admin/clientes`, `/admin/articulos`, `/admin/entradas`, `/admin/pedidos` -> CRUD operativo. `routes/web.php:68-109`.
- `GET /admin/configuracion` -> Estado y administración del sistema. `routes/web.php:93-104`.
- `GET /admin/personalizacion` -> Branding y logo. `routes/web.php:104-104`.
- `GET /admin/scoring/analytics` -> analítica de clientes y scoring. `routes/web.php:104-104`.

## Principios de gobernanza

- Todas las decisiones de negocio deben ser trazables a rutas, controladores o migraciones con evidencia directa.
- La marca y el nombre del sistema deben vivir en `AppSetting` y no en texto hardcodeado dentro de vistas.
- Las notificaciones de WhatsApp deben ser tratadas como cola de negocio y no como ejecución directa del flujo principal.
