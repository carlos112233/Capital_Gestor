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
                ->where('roles.name', 'admin')
                ->whereNotNull('users.telefono')
                ->where('users.telefono', '!=', '')
                ->pluck('users.telefono')
                ->map(function ($tel) {
                    $num = preg_replace('/[^0-9]/', '', $tel);
                    return (strlen($num) == 10) ? '521' . $num : $num;
                })
                ->filter()
                ->unique()
                ->toArray();

            if (empty($adminPhones)) {
                return; // No admin phones configured, do nothing
            }

            $venta->loadMissing(['user', 'articulo']);
            $articuloNombre = $venta->articulo->nombre ?? 'N/A';
            $clienteNombre  = $venta->user->name ?? 'Cliente';
            $totalFormatted = number_format($venta->total_venta ?? 0, 2);
            $mensajeWa = "*🛒 NUEVA COMPRA / VENTA #{$venta->id} - El rico bajon*\n\n" .
                         "• *Cliente:* {$clienteNombre}\n" .
                         "• *Artículo:* {$articuloNombre}\n" .
                         "• *Cantidad:* {$venta->cantidad}\n" .
                         "• *Total:* \${$totalFormatted}\n" .
                         "• *Notas:* " . ($venta->descripcion ?: 'Sin notas') . "\n\n" .
                         "_Enviado por El rico bajon CRM_";

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
