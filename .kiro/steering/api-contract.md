# API Contract Steering

## Rutas web activas

### Auth

- `GET /login` -> `AuthenticatedSessionController@create` (`routes/auth.php:12-17`)
- `POST /login` -> `AuthenticatedSessionController@store` (`routes/auth.php:18-20`)
- `POST /logout` -> `AuthenticatedSessionController@destroy` (`routes/auth.php:57-61`)

### Dashboard y administración

- `GET /dashboard` -> `DashboardController@index` (`routes/web.php:26-46`)
- `GET /dashboardAdmin` -> `DashboardController@indexAdmin` (`routes/web.php:10-19`)
- `GET /admin/configuracion` -> `ConfiguracionController@index` (`routes/web.php:91-104`)
- `POST /admin/configuracion/logo` -> `ConfiguracionController@updateLogo` (`routes/web.php:93-95`)
- `GET /admin/personalizacion` -> `BrandingController@index` (`routes/web.php:104-104`)
- `POST /admin/personalizacion` -> `BrandingController@update` (`routes/web.php:104-104`)

### Recursos principales

- `Route::resource('ventas', VentaController::class)` (`routes/web.php:58-60`)
- `Route::resource('pedidos', PedidoController::class)` (`routes/web.php:40-41`)
- `Route::resource('articulos', ArticuloController::class)` (`routes/web.php:78-81`)
- `Route::resource('clientes', ClienteController::class)` (`routes/web.php:72-76`)
- `Route::resource('entradas', EntradaController::class)` (`routes/web.php:82-83`)

## Rutas API Sanctum

- `POST /login` -> `Api\AuthController@login` (`routes/api.php:11-12`)
- `GET /profile` -> `Api\ProfileApiController@show` (`routes/api.php:16-20`)
- `PATCH /profile` -> `Api\ProfileApiController@update` (`routes/api.php:17-20`)
- `GET /dashboard` -> `Api\DashboardApiController@usuario` (`routes/api.php:21-22`)
- `GET /catalogo` -> `Api\CatalogoApiController@index` (`routes/api.php:23-24`)
- `apiResource('ventas')` -> `Api\VentaApiController` (`routes/api.php:31-32`)
- `apiResource('pedidos')` -> `Api\PedidoApiController` (`routes/api.php:31-32`)
- `apiResource('articulos')` -> `Api\ArticuloApiController` (`routes/api.php:35-40`)

## Payloads y respuestas

- Los controladores principales validan payloads con `request()->validate()` o con `FormRequest` si aplica; por ejemplo, `VentaController@store` hace validación explícita de `articulo_id`, `cantidad`, `cliente_id`, `precio_venta` y `descripcion`. `app/Http/Controllers/VentaController.php:85-165`.
- Los archivos con branding usan validaciones de imagen; `logo` acepta `mimes:jpeg,png,jpg,gif,svg,webp` y `favicon` acepta `ico,png,x-icon`. `app/Http/Controllers/BrandingController.php:58-90`.
- Las respuestas se entregan principalmente como redirecciones y vistas Blade en web, y como JSON con Sanctum en API. `routes/web.php` y `routes/api.php`.

## Conocimiento transversal

- La aplicación usa `role:admin` en middleware para protecciones de rutas admin.
- Las rutas con `apiResource` no muestran `create` ni `edit` la intención de un API RESTful. `routes/api.php:15-42`.
- No se ha encontrado una especificación OpenAPI formal del contrato; la fuente de verdad continúa siendo el mapeo de rutas y los controladores. `routes/api.php:1-42`.
