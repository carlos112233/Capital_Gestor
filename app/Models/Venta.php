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
            $adminPhones = \Illuminate\Support\Facades\DB::table('users')
                ->join('role_user', 'users.id', '=', 'role_user.user_id')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(roles.name)'), ['admin', 'administrador'])
                ->whereNotNull('users.telefono')
                ->where('users.telefono', '!=', '')
                ->pluck('users.telefono')
                ->map(function ($tel) {
                    $num = preg_replace('/[^0-9]/', '', $tel);
                    if (strlen($num) == 10) return '521' . $num;
                    if (strlen($num) == 12 && str_starts_with($num, '52')) return '521' . substr($num, 2);
                    return $num;
                })
                ->filter()
                ->unique()
                ->toArray();

            if (empty($adminPhones)) {
                $adminPhones = ['5212222153410'];
            }

            $venta->loadMissing(['user', 'articulo']);
            $articuloNombre = $venta->articulo->nombre ?? 'N/A';
            $clienteNombre  = $venta->user->name ?? 'Cliente';
            $totalFormatted = number_format($venta->total_venta ?? 0, 2);
            $mensajeWa = "*🛒 NUEVA COMPRA / VENTA #{$venta->id} - El bajon*\n\n" .
                         "• *Cliente:* {$clienteNombre}\n" .
                         "• *Artículo:* {$articuloNombre}\n" .
                         "• *Cantidad:* {$venta->cantidad}\n" .
                         "• *Total:* \${$totalFormatted}\n" .
                         "• *Notas:* " . ($venta->descripcion ?: 'Sin notas') . "\n\n" .
                         "_Enviado por El bajon_";

            foreach ($adminPhones as $telAdmin) {
                \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                    'numero'     => $telAdmin,
                    'mensaje'    => $mensajeWa,
                    'status'     => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            \Illuminate\Support\Facades\Log::info("Notificación WhatsApp de Nueva Compra/Venta #{$venta->id} encolada para admins: " . implode(', ', $adminPhones));
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
            $adminPhones = \Illuminate\Support\Facades\DB::table('users')
                ->join('role_user', 'users.id', '=', 'role_user.user_id')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(roles.name)'), ['admin', 'administrador'])
                ->whereNotNull('users.telefono')
                ->where('users.telefono', '!=', '')
                ->pluck('users.telefono')
                ->map(function ($tel) {
                    $num = preg_replace('/[^0-9]/', '', $tel);
                    if (strlen($num) == 10) return '521' . $num;
                    if (strlen($num) == 12 && str_starts_with($num, '52')) return '521' . substr($num, 2);
                    return $num;
                })
                ->filter()
                ->unique()
                ->toArray();

            if (empty($adminPhones)) {
                $adminPhones = ['5212222153410'];
            }

            if (empty($ventas)) return;

            $clienteNombre = $ventas[0]->user->name ?? 'Cliente';
            
            $mensajeWa = "*🛒 NUEVO CARRITO / COMPRA MÚLTIPLE - El bajon*\n\n" .
                         "• *Cliente:* {$clienteNombre}\n\n*PRODUCTOS:*\n";

            foreach ($ventas as $venta) {
                $articuloNombre = $venta->articulo->nombre ?? 'N/A';
                $totalItem = number_format($venta->total_venta ?? 0, 2);
                $mensajeWa .= "• {$articuloNombre} (x{$venta->cantidad}) = \${$totalItem}\n" .
                              "  _Notas:_ " . ($venta->descripcion ?: 'Sin notas') . "\n";
            }

            $totalFormatted = number_format($totalCarrito, 2);
            $mensajeWa .= "\n*TOTAL DEL CARRITO:* \${$totalFormatted}\n\n" .
                         "_Enviado por El bajon_";

            foreach ($adminPhones as $telAdmin) {
                \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                    'numero'     => $telAdmin,
                    'mensaje'    => $mensajeWa,
                    'status'     => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            \Illuminate\Support\Facades\Log::info("Notificación WhatsApp de Nueva Compra/Venta #{$venta->id} encolada para admins: " . implode(', ', $adminPhones));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error encolando WhatsApp de Nueva Compra/Venta #{$venta->id}: " . $e->getMessage());
        }
    }
}
