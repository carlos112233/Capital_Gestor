# API Contract Steering — Capital Gestor (Taller Velázquez)

> Catálogo de contratos de interfaz, endpoints REST y respuestas oficiales · Última actualización: 2026-11-07
> Convenciones: **[HECHO]** · **[INFERENCIA]** · **[DUDA]** · `❓ NO DETERMINABLE CON EL CÓDIGO`.

---

## 1. Convenciones Globales y Autenticación

- **Base URL API:** `/api`
- **Mecanismo de Autenticación:** Laravel Sanctum Bearer Token en cabecera `Authorization: Bearer <token>` (`routes/api.php:20`).
- **Estructura Estándar de Respuesta:** Orquestada por el trait `App\Traits\ApiResponse` (`app/Traits/ApiResponse.php:9-27`):

```json
// Éxito (HTTP 200 / 201)
{
  "success": true,
  "message": "Operación realizada con éxito",
  "data": { ... }
}

// Error (HTTP 400 / 401 / 403 / 404 / 422)
{
  "success": false,
  "message": "Descripción del error",
  "errors": { ... }
}
```

---

## 2. Catálogo de Endpoints REST (`routes/api.php`)

### 2.1 Autenticación

#### `POST /api/login`
- **Middleware:** `throttle:6,1` (`routes/api.php:16`)
- **Controlador:** `App\Http\Controllers\Api\AuthController@login` (`app/Http/Controllers/Api/AuthController.php:12-43`)
- **Payload Request:**
  ```json
  {
    "email": "usuario@ejemplo.com",
    "password": "password123"
  }
  ```
- **Response 200:**
  ```json
  {
    "token": "1|sanctum_plain_text_token...",
    "user": {
      "id": 1,
      "name": "Administrador",
      "telefono": "5212222153410",
      "email": "admin@ejemplo.com",
      "image": "avatars/avatar1.png",
      "roles": ["admin"]
    }
  }
  ```
- **Response 401:** `{"message": "Credenciales inválidas"}`

#### `POST /api/logout`
- **Middleware:** `auth:sanctum` (`routes/api.php:51`)
- **Controlador:** `App\Http\Controllers\Api\AuthController@logout`
- **Response 200:** `{"message": "Sesión cerrada"}`

---

### 2.2 Endpoints Operativos (`auth:sanctum`)

| Método | Endpoint | Controlador | Parámetros / Payload Clave | Descripción |
|---|---|---|---|---|
| `GET` | `/api/profile` | `ProfileApiController@show` | — | Perfil del usuario autenticado |
| `PATCH` | `/api/profile` | `ProfileApiController@update` | `name`, `email`, `telefono`, `image` | Actualización de perfil |
| `GET` | `/api/dashboard` | `DashboardApiController@usuario` | `search` (opcional) | Balance financiero y compras del cliente |
| `GET` | `/api/catalogo` | `CatalogoApiController@index` | `q` (búsqueda de producto) | Lista artículos con stock disponible |
| `POST` | `/api/catalogo/vender` | `CatalogoApiController@vender` | `articulo_id`, `cantidad`, `precio_venta`, `descripcion` | Registra venta deduciendo inventario |
| `GET` | `/api/ventas` | `VentaApiController@index` | `search` | Historial de ventas del usuario |
| `POST` | `/api/ventas` | `VentaApiController@store` | `articulo_id`, `cantidad`, `descripcion` | Crea venta con precio tomado del catálogo |
| `GET` | `/api/pedidos` | `PedidoApiController@index` | `search` | Lista de órdenes en preparación |
| `POST` | `/api/pedidos` | `PedidoApiController@store` | `articulo_id`, `cantidad`, `costo`, `descripcion` | Encola pedido para cocina/taller |
| `GET` | `/api/entradas` | `EntradaApiController@index` | `search` | Relación de abonos y cobros procesados |
| `GET` | `/api/clientes` | `ClienteApiController@index` | `search` | Lista de clientes registrados |

---

### 2.3 Endpoints Administrativos (`auth:sanctum`, `role:admin`, prefijo `/api/admin`)

| Método | Endpoint | Controlador | Descripción |
|---|---|---|---|
| `GET` | `/api/admin/dashboard` | `DashboardApiController@admin` | Métricas globales de venta, cartera vencida y pedidos |
| `apiResource` | `/api/admin/articulos` | `ArticuloApiController` | CRUD completo de artículos y stock |
| `apiResource` | `/api/admin/clientes` | `ClienteApiController` | CRUD y asignación de clientes |
| `apiResource` | `/api/admin/entradas` | `EntradaApiController` | Gestión de abonos de capital |
| `apiResource` | `/api/admin/pedidos` | `PedidoApiController` | Modificación de estatus de órdenes |
