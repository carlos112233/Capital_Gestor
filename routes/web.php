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
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Http;

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->name('session');

Route::middleware(['auth', 'role:admin'])->group(function () {
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
    Route::get('cobros', [\App\Http\Controllers\CobroController::class, 'index'])->name('cobros.index');

    // Quejas, Comentarios y Sugerencias (Feedback del usuario)
    Route::get('feedback', [\App\Http\Controllers\FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('feedback', [\App\Http\Controllers\FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('feedback/{feedback}', [\App\Http\Controllers\FeedbackController::class, 'show'])->name('feedback.show');
    Route::post('feedback/{feedback}/reply', [\App\Http\Controllers\FeedbackController::class, 'reply'])->name('feedback.reply');
    Route::post('feedback/{feedback}/status', [\App\Http\Controllers\FeedbackController::class, 'updateStatus'])->name('feedback.status');

    Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('clientes', ClienteController::class);
        Route::resource('articulos', ArticuloController::class);
        Route::post('articulos/bulk-disponible', [ArticuloController::class, 'bulkDisponible'])->name('articulos.bulk-disponible');
        Route::post('articulos/{articulo}/toggle-disponible', [ArticuloController::class, 'toggleDisponible'])->name('articulos.toggle');
        Route::resource('entradas', EntradaController::class);
        Route::resource('pedidos', PedidoController::class);
        Route::get('configuracion', [\App\Http\Controllers\ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::post('configuracion/logo', [\App\Http\Controllers\ConfiguracionController::class, 'updateLogo'])->name('configuracion.logo');
        Route::get('configuracion/wa-status', [\App\Http\Controllers\ConfiguracionController::class, 'getWaStatus'])->name('configuracion.wa-status');
        Route::post('configuracion/wa-reset', [\App\Http\Controllers\ConfiguracionController::class, 'resetWaSession'])->name('configuracion.wa-reset');
        Route::post('enviar-masivo', [DashboardController::class, 'enviarRecordatoriosMasivos'])->name('enviar.masivo');
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

Route::get('/enviar-alerta', function () {
    \Illuminate\Support\Facades\Notification::route('broadcast', 'canal-publico')
        ->notify(new GeneralNotification("Prueba Laravel 12", "¡Esto funciona!"));

    return "Notificación enviada.";
});

Route::get('/test-notif', function () {
    $topic = "canal_marcos_123"; 

    $response = Http::withHeaders([
        'Title' => 'Prueba Local',
        'Priority' => 'high',
        'Tags' => 'tada,iphone'
    ])->post("https://ntfy.sh/$topic", "¡Si lees esto, Laravel y ntfy funcionan!");

    return $response->ok() ? "Enviado correctamente" : "Error al enviar";
});