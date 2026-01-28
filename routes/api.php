<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticuloApiController; // Controlador que crearemos para el login
// Importa tus controladores normales
use App\Http\Controllers\Api\EntradaApiController;
use App\Http\Controllers\Api\CatalogoApiController;
use App\Http\Controllers\Api\VentaApiController;
use App\Http\Controllers\Api\PedidoApiController;
use App\Http\Controllers\Api\ClienteApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;

// --- RUTAS PÚBLICAS ---
Route::post('/login', [AuthController::class, 'login']); // Ruta para obtener el token


// --- RUTAS PROTEGIDAS (Cualquier usuario autenticado) ---
Route::middleware('auth:sanctum')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileApiController::class, 'edit']);
    Route::patch('/profile', [ProfileApiController::class, 'update']);
    Route::delete('/profile', [ProfileApiController::class, 'destroy']);
     Route::get('/dashboard', [DashboardApiController::class, 'usuario']);
    // // Catálogo
     Route::get('catalogo', [CatalogoApiController::class, 'index']);
     Route::post('catalogo/vender', [CatalogoApiController::class, 'vender']);

    // // Recursos (apiResource elimina create y edit)
     Route::apiResource('pedidos', PedidoApiController::class);
     Route::apiResource('ventas', VentaApiController::class);
     // --- RUTAS DE ADMIN (Protegidas por token + rol) ---
    Route::middleware('role:admin')->prefix('admin')->group(function () {  
        Route::apiResource('clientes', ClienteApiController::class);      
        Route::apiResource('articulos', ArticuloApiController::class);
         Route::apiResource('entradas', EntradaApiController::class);
         Route::apiResource('pedidos', PedidoApiController::class);
        Route::get('dashboard', [DashboardApiController::class, 'admin']);
    });
   

    // Ruta para cerrar sesión (revocar token)
    Route::post('/logout', [AuthController::class, 'logout']);
});
