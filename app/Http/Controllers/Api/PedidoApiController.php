<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Venta;
use App\Models\Articulo;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NuevoPedidoNotification;

class PedidoApiController extends Controller
{
    use ApiResponse;

    /**
     * Lista de pedidos (Admin ve todo, Usuario ve lo suyo)
     */
    public function index()
    {
        $user = Auth::user();

        $query = Pedido::with(['user', 'articulo', 'venta'])->latest();

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        $pedidos = $query->paginate(15);

        return $this->success($pedidos);
    }

    /**
     * Crear múltiples pedidos y ventas en una sola petición
     */
    public function store(Request $request)
    {
        $request->validate([
            'pedidos' => 'required|array|min:1',
            'pedidos.*.articulo_id' => 'required|exists:articulos,id',
            'pedidos.*.cantidad'    => 'required|integer|min:1',
            'pedidos.*.costo'       => 'required|numeric|min:1',
            'pedidos.*.descripcion' => 'nullable|string',
            'pedidos.*.user_id'     => 'nullable|exists:users,id',
        ]);

        try {
            $resultados = DB::transaction(function () use ($request) {
                $creados = [];

                foreach ($request->pedidos as $p) {
                    // Determinar ID del usuario (Admin puede asignar a otros)
                    $userId = Auth::user()->hasRole('admin') 
                        ? ($p['user_id'] ?? Auth::id()) 
                        : Auth::id();

                    $total = $p['costo'] * $p['cantidad'];

                    // 1. Crear la venta
                    $venta = Venta::create([
                        'user_id'      => $userId,
                        'articulo_id'  => $p['articulo_id'],
                        'cantidad'     => $p['cantidad'],
                        'precio_venta' => $p['costo'],
                        'total_venta'  => $total,
                        'descripcion'  => $p['descripcion'] ?? '',
                    ]);

                    // 2. Crear el pedido
                    $pedido = Pedido::create([
                        'user_id'     => $userId,
                        'articulo_id' => $p['articulo_id'],
                        'descripcion' => $p['descripcion'] ?? '',
                        'costo'       => $total,
                        'venta_id'    => $venta->id,
                        'cantidad'    => $p['cantidad'],
                    ]);

                    // 3. Notificación (Opcional: puedes moverlo fuera del loop o usar colas)
                    Notification::route('mail', 'gestorcapital.0925@gmail.com')
                        ->notify(new NuevoPedidoNotification($pedido));

                    $creados[] = $pedido;
                }
                return $creados;
            });

            return $this->success($resultados, 'Pedidos y ventas creados correctamente.', 201);

        } catch (\Exception $e) {
            return $this->error('Error al procesar los pedidos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Ver detalle de un pedido
     */
    public function show($id)
    {
        $pedido = Pedido::with(['user', 'articulo', 'venta'])->find($id);

        if (!$pedido) {
            return $this->error('Pedido no encontrado', 404);
        }

        if (!Auth::user()->hasRole('admin') && $pedido->user_id != Auth::id()) {
            return $this->error('No autorizado', 403);
        }

        return $this->success($pedido);
    }

    /**
     * Actualizar pedidos (Soporta actualización masiva)
     */
    public function update(Request $request)
    {
        $request->validate([
            'pedidos' => 'required|array|min:1',
            'pedidos.*.id'           => 'nullable|exists:pedidos,id',
            'pedidos.*.articulo_id'  => 'required|exists:articulos,id',
            'pedidos.*.cantidad'     => 'required|integer|min:1',
            'pedidos.*.costo'        => 'required|numeric|min:1',
            'pedidos.*.descripcion'  => 'nullable|string',
            'pedidos.*.user_id'      => 'nullable|exists:users,id',
        ]);

        try {
            $actualizados = DB::transaction(function () use ($request) {
                $lista = [];
                foreach ($request->pedidos as $p) {
                    $total = $p['costo'] * $p['cantidad'];
                    $userId = Auth::user()->hasRole('admin') ? ($p['user_id'] ?? Auth::id()) : Auth::id();

                    if (!empty($p['id'])) {
                        $pedido = Pedido::findOrFail($p['id']);
                        
                        // Seguridad básica
                        if (!Auth::user()->hasRole('admin') && $pedido->user_id != Auth::id()) continue;

                        $pedido->update([
                            'articulo_id' => $p['articulo_id'],
                            'descripcion' => $p['descripcion'] ?? '',
                            'costo'       => $total,
                            'cantidad'    => $p['cantidad'],
                            'user_id'     => $userId,
                        ]);

                        if ($pedido->venta) {
                            $pedido->venta->update([
                                'articulo_id'  => $p['articulo_id'],
                                'cantidad'     => $p['cantidad'],
                                'precio_venta' => $p['costo'],
                                'total_venta'  => $total,
                                'user_id'      => $userId,
                                'descripcion'  => $p['descripcion'] ?? '',
                            ]);
                        }
                        $lista[] = $pedido;
                    }
                }
                return $lista;
            });

            return $this->success($actualizados, 'Pedidos actualizados correctamente.');
        } catch (\Exception $e) {
            return $this->error('Error al actualizar: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar un pedido
     */
    public function destroy($id)
    {
        try {
            $pedido = Pedido::with('venta')->find($id);

            if (!$pedido) return $this->error('Pedido no encontrado', 404);

            if (!Auth::user()->hasRole('admin') && $pedido->user_id !== Auth::id()) {
                return $this->error('No autorizado', 403);
            }

            DB::transaction(function () use ($pedido) {
                $venta = $pedido->venta;
                $pedido->delete();

                // Si la venta ya no tiene más pedidos asociados, eliminarla
                if ($venta && $venta->pedidos()->count() === 0) {
                    $venta->delete();
                }
            });

            return $this->success(null, 'Pedido y venta asociada eliminados correctamente.');
        } catch (\Exception $e) {
            return $this->error('Error al eliminar el pedido', 500);
        }
    }
}