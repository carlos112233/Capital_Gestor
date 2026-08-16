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

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->hasRole('admin')) {
            return redirect()->route('dashboardAdmin');
        }
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('session');

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
    Route::get('/user/{id}/image', [ProfileController::class, 'showImage'])->name('user.image');
    Route::get('catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
    Route::post('catalogo/vender', [CatalogoController::class, 'vender'])->name('catalogo.vender');
    Route::resource('pedidos', PedidoController::class);
    Route::post('ventas/multiples', [VentaController::class, 'storeMultiple'])->name('ventas.storeMultiple');
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
        Route::post('clientes/{cliente}/activar', [ClienteController::class, 'activar'])->name('clientes.activar');
        Route::post('clientes/{cliente}/enviar-whatsapp', [ClienteController::class, 'enviarWhatsAppAccess'])->name('clientes.enviar-whatsapp');
        Route::resource('articulos', ArticuloController::class);
        Route::post('articulos/bulk-disponible', [ArticuloController::class, 'bulkDisponible'])->name('articulos.bulk-disponible');
        Route::post('articulos/{articulo}/toggle-disponible', [ArticuloController::class, 'toggleDisponible'])->name('articulos.toggle');
        Route::resource('entradas', EntradaController::class);
        Route::resource('pedidos', PedidoController::class);
        Route::get('configuracion', [\App\Http\Controllers\ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::post('configuracion/logo', [\App\Http\Controllers\ConfiguracionController::class, 'updateLogo'])->name('configuracion.logo');
        Route::get('configuracion/wa-status', [\App\Http\Controllers\ConfiguracionController::class, 'getWaStatus'])->name('configuracion.wa-status');
        Route::post('configuracion/wa-reset', [\App\Http\Controllers\ConfiguracionController::class, 'resetWaSession'])->name('configuracion.wa-reset');
        Route::post('configuracion/wa-mark-sent/{id}', [\App\Http\Controllers\ConfiguracionController::class, 'markMessageAsSent'])->name('configuracion.wa-mark-sent');
        Route::post('configuracion/wa-mark-pending/{id}', [\App\Http\Controllers\ConfiguracionController::class, 'markMessageAsPending'])->name('configuracion.wa-mark-pending');
        Route::post('enviar-masivo', [DashboardController::class, 'enviarRecordatoriosMasivos'])->name('enviar.masivo');
        
        // Comprobantes Admin
        Route::post('comprobantes/{id}/aprobar', [\App\Http\Controllers\ComprobanteController::class, 'aprobar'])->name('comprobantes.aprobar');
        Route::post('comprobantes/{id}/rechazar', [\App\Http\Controllers\ComprobanteController::class, 'rechazar'])->name('comprobantes.rechazar');
    });

    // Comprobantes Usuario
    Route::post('comprobantes', [\App\Http\Controllers\ComprobanteController::class, 'store'])->name('comprobantes.store');
    
    // -------------------------------------------------------------
    // Notificaciones Web Push y DB
    // -------------------------------------------------------------
    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\PushSubscriptionController::class, 'markAllRead'])->name('notifications.markAllRead');
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