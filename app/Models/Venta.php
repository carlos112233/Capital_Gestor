<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comprador_id',
        'articulo_id',
        'cliente_id',
        'cantidad',
        'precio_venta',
        'total_venta',
        'descripcion',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comprador_id')->withTrashed();
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'venta_id');
    }

    /**
     * Envía una alerta automática por WhatsApp al Administrador ante una nueva compra/venta de artículo.
     */
    public static function notificarAdminWhatsApp(self $venta): void
    {
        try {
            $waService = app(\App\Services\WhatsAppNotificationService::class);
            $adminPhones = $waService->getAdminPhones();

            $venta->loadMissing(['user', 'articulo']);
            $articuloNombre = $venta->articulo->nombre ?? 'N/A';
            $clienteNombre  = $venta->user->name ?? 'Cliente';
            $totalFormatted = number_format($venta->total_venta ?? 0, 2);
            $cant           = (int) ($venta->cantidad ?? 1);
            $appName        = \App\Models\AppSetting::getSettings()->app_name ?? 'El Bajón';

            $mensajeWa = "*🛒 NUEVA COMPRA / VENTA #{$venta->id} - {$appName}*\n\n" .
                         "• *Cliente:* {$clienteNombre}\n" .
                         "• *Total de productos:* {$cant} unidad(es)\n" .
                         "• *Artículo:* {$articuloNombre}\n" .
                         "• *Cantidad:* {$cant}\n" .
                         "• *Total:* \${$totalFormatted}\n" .
                         "• *Notas:* " . ($venta->descripcion ?: 'Sin notas') . "\n\n" .
                         "_Enviado por {$appName}_";

            $waService->enqueueToMultiple($adminPhones, $mensajeWa);
            \Illuminate\Support\Facades\Log::info("Notificación WhatsApp de Nueva Compra/Venta #{$venta->id} encolada para admins: " . implode(', ', $adminPhones));

            // Notificación Push a Administradores (Campana web, WebSockets y navegador)
            try {
                \App\Services\PushNotificationService::notifyAdmins(
                    "Nueva Compra Registrada 🛒",
                    "El cliente {$clienteNombre} compró {$cant} producto(s) por un total de \${$totalFormatted}: {$cant}x {$articuloNombre}.",
                    route('ventas.index')
                );
            } catch (\Throwable $ePush) {
                \Illuminate\Support\Facades\Log::error("Error enviando Push de Nueva Compra #{$venta->id}: " . $ePush->getMessage());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error encolando WhatsApp de Nueva Compra/Venta #{$venta->id}: " . $e->getMessage());
        }
    }

    /**
     * Envía una alerta automática por WhatsApp al Administrador ante una compra agrupada (carrito).
     */
    public static function notificarCarritoAdminWhatsApp(array $ventas, float $totalCarrito): void
    {
        try {
            if (empty($ventas)) return;

            $waService = app(\App\Services\WhatsAppNotificationService::class);
            $adminPhones = $waService->getAdminPhones();

            $clienteNombre = $ventas[0]->user->name ?? 'Cliente';
            $appName = \App\Models\AppSetting::getSettings()->app_name ?? 'El Bajón';
            
            $totalUnidades = 0;
            $itemsPush = [];
            $lineasProductosWa = '';

            foreach ($ventas as $venta) {
                $venta->loadMissing(['articulo', 'user']);
                $articuloNombre = $venta->articulo->nombre ?? 'N/A';
                $cant = (int) ($venta->cantidad ?? 1);
                $totalUnidades += $cant;
                $totalItem = number_format($venta->total_venta ?? 0, 2);

                $itemsPush[] = "{$cant}x {$articuloNombre}";
                $lineasProductosWa .= "• {$articuloNombre} (x{$cant}) = \${$totalItem}\n" .
                                      "  _Notas:_ " . ($venta->descripcion ?: 'Sin notas') . "\n";
            }

            $totalFormatted = number_format($totalCarrito, 2);
            $listaProductosPush = \Illuminate\Support\Str::limit(implode(', ', $itemsPush), 180, '...');

            $mensajeWa = "*🛒 NUEVO CARRITO / COMPRA MÚLTIPLE - {$appName}*\n\n" .
                         "• *Cliente:* {$clienteNombre}\n" .
                         "• *Total de productos:* {$totalUnidades} unidad(es)\n\n" .
                         "*PRODUCTOS:*\n" .
                         $lineasProductosWa . "\n" .
                         "*TOTAL DEL CARRITO:* \${$totalFormatted}\n\n" .
                         "_Enviado por {$appName}_";

            $waService->enqueueToMultiple($adminPhones, $mensajeWa);

            \Illuminate\Support\Facades\Log::info("Notificación WhatsApp de Carrito encolada para admins: " . implode(', ', $adminPhones));

            // Notificación Push a Administradores (Campana web, WebSockets y navegador)
            try {
                \App\Services\PushNotificationService::notifyAdmins(
                    "Nueva Compra Múltiple (Carrito) 🛒",
                    "El cliente {$clienteNombre} compró {$totalUnidades} producto(s) por un total de \${$totalFormatted}: {$listaProductosPush}.",
                    route('ventas.index')
                );
            } catch (\Throwable $ePush) {
                \Illuminate\Support\Facades\Log::error("Error enviando Push de Carrito: " . $ePush->getMessage());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error encolando WhatsApp de Carrito: " . $e->getMessage());
        }
    }
}
