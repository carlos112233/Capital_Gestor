# 02 - Modelo de Datos, Persistencia e Integraciones Externas

## 1) Diagrama ER (Mermaid)

```mermaid
erDiagram
    USERS ||--o{ ROLE_USER : has
    ROLES ||--o{ ROLE_USER : maps
    USERS ||--o{ VENTAS : sells
    USERS ||--o{ ENTRADAS : records
    USERS ||--o{ PEDIDOS : creates
    ARTICULOS ||--o{ VENTAS : sold_as
    ARTICULOS ||--o{ ENTRADAS : receives
    ARTICULOS ||--o{ PEDIDOS : includes
    USERS ||--o{ COMPROBANTES : submits
    APP_SETTINGS ||..o USERS : app_branding
    WHATSAPP_PENDING_MESSAGES }o--|| USERS : notified

    USERS {
      bigint id PK
      string name
      string email
      string password
      string telefono
      string image
      datetime deleted_at
    }
    ROLES {
      bigint id PK
      string name
    }
    ROLE_USER {
      bigint id PK
      bigint role_id FK
      bigint user_id FK
    }
    ARTICULOS {
      bigint id PK
      string nombre
      text descripcion
      decimal precio
      integer stock
    }
    VENTAS {
      bigint id PK
      bigint user_id FK
      bigint comprador_id FK
      bigint articulo_id FK
      decimal precio_venta
      decimal total_venta
      integer cantidad
      string descripcion
    }
    ENTRADAS {
      bigint id PK
      bigint user_id FK
      bigint cliente_id FK
      bigint articulo_id FK
      decimal precio_venta
      date fecha_generado
      text descripcion
    }
    PEDIDOS {
      bigint id PK
      bigint user_id FK
      bigint articulo_id FK
      bigint venta_id FK
      decimal costo
      string descripcion
    }
    COMPROBANTES {
      bigint id PK
      bigint user_id FK
      decimal monto
      string imagen
      enum status
    }
    APP_SETTINGS {
      bigint id PK
      string app_name
      string logo_path
      string favicon_path
      string primary_color
      string secondary_color
      string sidebar_color
      string font_family
      enum theme_mode
    }
    WHATSAPP_PENDING_MESSAGES {
      bigint id PK
      string numero
      text mensaje
      string status
      text error_message
      string pdf_path
    }
```

## 2) Catálogo de modelos y migraciones

### `users`, `roles`, `role_user`

- `database/migrations/2025_08_12_173449_create_roles_table.php:8-22` crea `roles` con `name` único.
- `database/migrations/2025_08_12_180515_create_role_user_table.php:8-25` crea la tabla pivote; usa `foreignId('role_id')->constrained()->onDelete('cascade')` y `foreignId('user_id')->constrained()->onDelete('cascade')`.
- `app/Models/User.php:25-38` define `fillable` de usuario y `app/Models/User.php:96-127` expone `roles()`, `entradas()`, `ventas()`, etc.
- `app/Models/User.php:310-322` define `hasRole(string $roleName): bool` y valida ambos aliases `cliente`/`user`.

### Inventario y ventas

- `database/migrations/2025_08_12_222329_create_articulos_table.php:8-21` define `articulos` con `nombre`, `descripcion`, `precio` y `stock`.
- `database/migrations/2025_08_12_230600_create_ventas_table.php:8-26` define ventas con `user_id`, `articulo_id`, `precio_venta`, `total_venta` y `descripcion`.
- `database/migrations/2025_08_13_191738_create_entradas_table.php:8-25` define entradas con `user_id`, `cliente_id`, `articulo_id`, `precio_venta`, `fecha_generado` y `descripcion`.
- `database/migrations/2025_09_02_183222_create_pedidos_table.php:8-26` define pedidos con `user_id`, `articulo_id`, `venta_id` y `costo` y agrega índices sobre `user_id` y `created_at`.

### Configuración y marca

- `database/migrations/2026_10_16_000001_create_app_settings_table.php:8-29` crea `app_settings` con nombre de marca, rutas de `logo_path`/`favicon_path`, colores, y `theme_mode`.
- `app/Models/AppSetting.php:11-118` encapsula la cache `app_settings`, el refresco en `booted()` y la resolución de URLs de almacenamiento.
- `app/Http/Controllers/BrandingController.php:77-168` sube logo/favicon con validación de tipos y tamaños, y elimina archivos previos antes de guardar.

### WhatsApp y cola de mensajes

- `database/migrations/2026_05_29_125352_create_whatsapp_pending_messages_table.php:8-28` crea la cola con `numero`, `mensaje`, `status` y timestamps.
- `database/migrations/2026_06_30_172430_add_error_message_to_whatsapp_pending_messages.php:8-27` añade `error_message` y `database/migrations/2026_08_28_204500_add_pdf_path_to_whatsapp_pending_messages.php:8-28` añade `pdf_path`.
- `app/Models/Venta.php:60-115` inserta mensajes pendientes en `whatsapp_pending_messages` cuando se registra una compra/venta, con estados `pendiente` y logs de seguimiento.
- `app/Http/Controllers/ConfiguracionController.php:11-31` consulta los mensajes pendientes para mostrarlos en el panel administrativo.

## 3) Mecanismo de roles y permisos

- `RoleSeeder` crea `admin`, `vendedor`, `cocina` y `cliente` en `database/seeders/RoleSeeder.php:8-18`.
- `User::roles()` define la relación MuchosAMuchos con `Role::class` en `app/Models/User.php:96-111`.
- Los checks se usan en controladores y middleware; por ejemplo, `app/Http/Middleware/RoleMiddleware.php:15-24` aborta con `403` si el usuario no cumple el rol requerido.
- `auth()->user()->hasRole('admin')` se utiliza para redirección y permisos en `app/Http/Controllers/Auth/AuthenticatedSessionController.php:28-58`.

## 4) Integraciones externas y sistema de archivos

### WhatsApp motor

- `docker-compose.yml:64-88` define el servicio `wa-motor` basado en Node.js con volumen persistente `.wwebjs_auth`.
- `app/Models/Venta.php:60-115` y `app/Models/Feedback.php:102-118` registran mensajes en `whatsapp_pending_messages`; desde ahí el motor del taller puede consumarlos.
- `app/Http/Controllers/ConfiguracionController.php:82-118` verifica el estado del motor usando `pgrep -f "wa-motor/index.js"` y arranca el proceso si falta.
- ❓ NO DETERMINABLE CON EL CÓDIGO: no existe evidencia explícita de un daemon Node/puppeteer que procese la cola; el repositorio demuestra la tabla de cola y el arranque del motor, pero no el consumidor exacto ni su protocolo de coordinación.

### Sistema de archivos / uploads

- `app/Models/User.php:63-80` y `app/Models/AppSetting.php:61-90` calculan URLs de imágenes y logo mediante `Storage::disk('public')`.
- `app/Http/Controllers/BrandingController.php:92-159` valida `logo` y `favicon` con `mimes:jpeg,png,jpg,gif,svg,webp` y `mimes:ico,png,x-icon`, y los guarda en `branding` dentro de `storage/app/public`.
- `app/Http/Controllers/ConfiguracionController.php:35-77` copia el logo a `public/img` para compatibilidad hacia atrás y limpia cache para sincronizar la marca en vistas.

## 5) Observaciones estructurales

- Hay un uso explícito de `decimal(10,2)` en ventas, entradas y artículos, lo que es apropiado para montos financieros; la persistencia está centrada en valores monetarios deterministas. `database/migrations/2025_08_12_222329_create_articulos_table.php:8-18`, `database/migrations/2025_08_12_230600_create_ventas_table.php:11-20`, `database/migrations/2025_08_13_191738_create_entradas_table.php:10-22`.
- El proyecto ya incorpora índices de rendimiento para `articulos`, `ventas` y `entradas` en migraciones posteriores, por ejemplo `database/migrations/2026_09_24_100409_add_performance_indexes_to_articulos_table.php:8-61` y `database/migrations/2026_10_15_000002_add_compound_indexes_to_ventas_and_entradas_tables.php:8-82`.
- La arquitectura actual prioriza lectura por modelos Eloquent, con un uso notable de `DB::table(...)` para colas y mensajes de WhatsApp y varias consultas directas de agregación financiera.
