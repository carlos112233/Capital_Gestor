<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\CatalogoController; 
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use GuzzleHttp\Client;

  Route::get('/', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Todas las rutas dentro de este grupo requerirán que el usuario esté autenticado
    // Y que tenga el rol de 'admin'
    Route::get('/admin/dashboard', function () {
        return view('dashboardAdmin');
    })->name('admin.dashboard');
    
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
    Route::post('catalogo/vender', [CatalogoController::class, 'vender'])->name('catalogo.vender');    
    
    Route::resource('ventas', VentaController::class);
    // --- AÑADIR ESTA LÍNEA ---
    Route::resource('entradas', EntradaController::class);
});


Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard de Admin (lo usaremos más adelante)
    // Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('clientes', ClienteController::class);
    Route::resource('articulos', ArticuloController::class);
   
});
require __DIR__.'/auth.php';
