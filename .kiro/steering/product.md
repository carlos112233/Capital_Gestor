# Product Steering — Capital Gestor (Taller Velázquez / El Bajón)

> Archivo de dirección operativa y de producto · Última actualización: 2026-11-07
> Convenciones: **[HECHO]** verificado en código · **[INFERENCIA]** deducción técnica · **[DUDA]** requiere definición del dueño · `❓ NO DETERMINABLE CON EL CÓDIGO`.

---

## 1. Resumen Ejecutivo del Negocio y Sistema

**Capital Gestor** es una plataforma web y móvil orientada al control operativo, ventas en punto de venta (POS), cobranza, catálogo de artículos y liquidación a vendedores/colaboradores.

- **[HECHO]** Identidad de marca en código: Inicialmente desarrollado bajo el concepto comercial **"El Bajón"** (`database/migrations/2026_10_16_000001_create_app_settings_table.php:16`, `resources/views/layouts/guest.blade.php:280`), con soporte de personalización multi-marca en caliente vía `AppSetting` (`app/Models/AppSetting.php:22-65`).
- **[HECHO]** No existe en la base de datos actual ninguna entidad automotriz (`vehiculos`, `placas`, `ordenes_reparacion`, `kilometraje`).
- **[DUDA]** Si la plataforma operará comercialmente como **"Taller Velázquez"**, las entidades actuales (`articulos`, `pedidos`, `ventas`, `entradas`, `comprobantes`) fungen como base abstracta de refacciones y servicios de mano de obra.

---

## 2. Roles del Sistema y Matriz de Acceso

Los roles están administrados vía la tabla `roles` y la relación muchos-a-muchos `role_user` (`database/migrations/2025_08_12_173449_create_roles_table.php:14-18`, `app/Models/User.php:108-112`).

| Rol | Identificador en BD | Responsabilidades y Alcance en Código |
|---|---|---|
| **Administrador** | `admin` (`RoleSeeder.php:16`) | Acceso total a dashboards (`dashboardAdmin`), configuración institucional, catálogos, aprobación/rechazo de comprobantes de pago, auditoría de ventas, reportes, liquidación de vendedores y control de WhatsApp. |
| **Vendedor / Operador** | `vendedor` (`RoleSeeder.php:17`) | Registro de ventas POS en catálogo, visualización de clientes, consulta de cobros y emisión de ventas rápidas/múltiples. |
| **Cocina / Taller Operativo** | `cocina` (`RoleSeeder.php:18`) | Rol heredado de restaurante. Recibe alertas y pedidos en preparación (`app/Http/Controllers/PedidoController.php:111`). |
| **Cliente / Usuario** | `cliente` (alias `user`) (`RoleSeeder.php:19`, `app/Models/User.php:310-321`) | Consulta de saldo deudor/pagado, catálogo de artículos, subida de comprobantes de transferencia y descarga de estado de cuenta PDF. |

---

## 3. Catálogo de Servicios y Conceptos Operativos

1. **Artículos e Inventario (`articulos`):** Refacciones, productos o servicios tasados en pesos (`decimal(10,2)`), con control de `stock` atómico y bandera `disponible` (`app/Models/Articulo.php:16-25`).
2. **Punto de Venta (`ventas`):** Registro de transacciones con bloqueo pesimista de fila (`lockForUpdate`), deducción de existencias y emisión de alertas (`app/Http/Controllers/VentaController.php:103-152`).
3. **Pedidos y Órdenes (`pedidos`):** Solicitudes encoladas con estado inicial `en_preparacion` y notificación instantánea a cocina/administración (`app/Http/Controllers/PedidoController.php:76-136`).
4. **Cobranza y Abonos (`entradas`):** Registro contable de ingresos por liquidación de saldos y abonos de clientes asociados a una fecha de corte (`app/Models/Entrada.php:13-21`).
5. **Comprobantes Bancarios OCR (`comprobantes`):** Subida de tickets o transferencias con pre-procesamiento automatizado mediante OCR (Tesseract / Google Cloud Vision) (`app/Services/ReceiptOcrService.php:12-637`).
6. **Liquidaciones y Retenciones (`pagos_vendedores`):** Nómina y retención porcentual a comisionistas/vendedores con notificación automatizada vía WhatsApp (`app/Http/Controllers/PagoVendedorController.php:179-284`).

---

## 4. Mapa de Pantallas del Panel Administrativo y Operativo

### 4.1 Pantallas Administrativas (`middleware: ['auth', 'role:admin']`)
- `/admin/dashboard` o `/dashboardAdmin`: Métricas financieras, saldo global, pedidos recientes y resumen de caja (`routes/web.php:26-29`).
- `/admin/clientes`: CRUD de usuarios y clientes, generación y reenvío de credenciales vía WhatsApp (`routes/web.php:69-71`).
- `/admin/articulos`: Catálogo maestro, ajuste masivo de disponibilidad y carga de fotografías (`routes/web.php:72-76`).
- `/admin/entradas`: Bitácora contable de cobros ingresados y reenvío de comprobantes PDF (`routes/web.php:77-78`).
- `/admin/pedidos`: Monitoreo y actualización del ciclo de vida de pedidos (`routes/web.php:79-80`).
- `/admin/configuracion`: Control del motor WhatsApp (código QR, reinicio de sesión y monitor de cola) (`routes/web.php:81-87`).
- `/admin/scoring/analytics`: Análisis de fiabilidad y riesgo financiero de clientes (`routes/web.php:95-96`).
- `/admin/pagos-vendedores`: Nómina, balance de retenciones porcentuales y finiquito a colaboradores (`routes/web.php:102-103`).
- `/admin/personalizacion`: Configuración institucional de marca (logotipo, favicon, colores corporativos y tema visual) (`routes/web.php:106-108`).

### 4.2 Pantallas Operativas y Generales (`middleware: ['auth']`)
- `/dashboard`: Panel general para vendedores y clientes con desglose de saldo pendiente y ventas acumuladas (`routes/web.php:34-36`).
- `/catalogo`: POS visual interactivo para registrar órdenes y transacciones en tiempo real (`routes/web.php:45-46`).
- `/ventas`: Listado de ventas realizadas con opción de solicitud de anulación (`routes/web.php:48-50`).
- `/cobros`: Monitor de cuentas por cobrar y balance deudores (`routes/web.php:52`).
- `/feedback`: Buzón bidireccional de incidencias y soporte (`routes/web.php:55-59`).
- `/manual-usuario`: Centro de ayuda y tutoriales guiados (`routes/web.php:129-130`).
- `/mi-tema`: Selector de preferencias visuales (modo oscuro, paleta personalizada) (`routes/web.php:135-137`).
