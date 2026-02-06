<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TransferenciaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Artisan;


Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->name('session');


Route::middleware(['auth', 'role:admin'])->group(function () {
    // Todas las rutas dentro de este grupo requerirán que el usuario esté autenticado
    // Y que tenga el rol de 'admin'
    Route::get('dashboardAdmin', [DashboardController::class, 'indexAdmin'])
        ->name('dashboardAdmin');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'indexUsuario'])
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
    Route::post('catalogo/vender', [CatalogoController::class, 'vender'])->name('catalogo.vender');
    Route::resource('pedidos', PedidoController::class);
    Route::resource('ventas', VentaController::class);
    Route::resource('datos', TransferenciaController::class);
    // --- AÑADIR ESTA LÍNEA ---



    Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard de Admin (lo usaremos más adelante)
        // Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::resource('clientes', ClienteController::class);
        Route::resource('articulos', ArticuloController::class);
        Route::resource('entradas', EntradaController::class);
        Route::resource('pedidos', PedidoController::class);
    });
});

require __DIR__ . '/auth.php';
Route::get('/descargar-log-secreto', function () {
    $path = storage_path('logs/laravel.log');

    if (file_exists($path)) {
        return response()->download($path);
    }

    return "El archivo de log aún no existe o está vacío.";
});
