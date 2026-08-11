<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Venta;
use App\Models\Articulo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Notifications\NuevoPedidoNotification;
use Illuminate\Support\Facades\Notification;

class PedidoController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Pedido::with(['user', 'articulo', 'venta'])->latest();

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        // Optimización de velocidad: restringir a los últimos 15 días
        $query->where('created_at', '>=', now()->subDays(15));

        if ($request->filled('q')) {
            $search = '%' . strtolower($request->input('q')) . '%';
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->whereRaw('LOWER(name) LIKE ?', [$search]);
                })->orWhereHas('articulo', function ($a) use ($search) {
                    $a->whereRaw('LOWER(nombre) LIKE ?', [$search]);
                })->orWhereRaw('LOWER(descripcion) LIKE ?', [$search]);
            });
        }

        $pedidos = $query->paginate(25)->withQueryString();
        // Cargar únicamente columnas necesarias para el select del modal (reduce uso de memoria y acelera la consulta SQL)
        $query = Articulo::select('id', 'nombre', 'precio')->orderBy('nombre', 'asc');
        if (!Auth::user()->hasRole('admin')) {
            $query->where('disponible', true);
        }
        $articulos = $query->get();
        $users = User::select('id', 'name')->orderBy('name', 'asc')->get();

        return view('pedidos.index', compact('pedidos', 'articulos', 'users'));
    }


    public function create()
    {
        $query = Articulo::orderBy('nombre', 'asc');
        if (!Auth::user()->hasRole('admin')) {
            $query->where('disponible', true);
        }
        $articulos = $query->get();
        $users = User::orderBy('name', 'asc')->get();
        $articuloId = null; // o algún valor por defecto, por ejemplo el primer artículo
        return view('pedidos.create', compact('articulos', 'articuloId', 'users'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'pedidos.*.articulo_id' => 'required|exists:articulos,id',
            'pedidos.*.cantidad'    => 'required|integer|min:1',
            'pedidos.*.costo'       => 'required|numeric|min:1',
            'pedidos.*.descripcion' => 'nullable|string',
            'pedidos.*.user_id'      => 'nullable|exists:users,id',
        ]);

        $userId = Auth::user()->hasRole('admin') ? $request->user_id : Auth::id();

        // 1. Obtener teléfonos de admin y cocina
        $adminPhones = \Illuminate\Support\Facades\DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.name', 'admin')
            ->whereNotNull('users.telefono')
            ->where('users.telefono', '!=', '')
            ->pluck('users.telefono')
            ->map(function ($tel) { return preg_replace('/[^0-9]/', '', $tel); })
            ->map(function ($num) { return (strlen($num) == 10) ? '521' . $num : $num; })
            ->filter()->unique()->toArray();
        if (empty($adminPhones)) $adminPhones = ['5212222153410'];

        $cocinaPhones = \Illuminate\Support\Facades\DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.name', 'cocina')
            ->whereNotNull('users.telefono')
            ->where('users.telefono', '!=', '')
            ->pluck('users.telefono')
            ->map(function ($tel) { return preg_replace('/[^0-9]/', '', $tel); })
            ->map(function ($num) { return (strlen($num) == 10) ? '521' . $num : $num; })
            ->filter()->unique()->toArray();

        // Variables para resumen de cocina
        $cemitasPollo = 0;
        $cemitasPuerco = 0;
        $tieneCemitas = false;
        $clienteNombreResumen = 'Cliente';

        foreach ($request->pedidos as $p) {
            $total = $p['costo'] * $p['cantidad'];
            $targetUserId = $p['user_id'] ?? $userId;

            $venta = Venta::create([
                'user_id'     => $targetUserId,
                'articulo_id'  => $p['articulo_id'],
                'cantidad'     => $p['cantidad'],
                'precio_venta' => $p['costo'],
                'total_venta'  => $total,
                'descripcion'  => $p['descripcion'] ?? '',
            ]);

            $pedido = Pedido::create([
                'user_id'     => $targetUserId,
                'articulo_id' => $p['articulo_id'],
                'descripcion' => $p['descripcion'] ?? '',
                'costo'       => $total,
                'venta_id'    => $venta->id,
                'cantidad'    => $p['cantidad'],
            ]);

            $pedido->load('articulo', 'user');

            try {
                $articuloNombre = $pedido->articulo->nombre ?? 'N/A';
                $clienteNombre  = $pedido->user->name ?? 'Cliente';
                $clienteNombreResumen = $clienteNombre;
                $descripcionStr = strtolower($articuloNombre . ' ' . ($pedido->descripcion ?? ''));

                $esCemita = (strpos($descripcionStr, 'cemita') !== false);

                if ($esCemita) {
                    $tieneCemitas = true;
                    $qty = (int) $pedido->cantidad;
                    if (strpos($descripcionStr, 'hawaiana') !== false || strpos($descripcionStr, 'cubana') !== false || strpos($descripcionStr, 'texana') !== false || strpos($descripcionStr, 'tejama') !== false) {
                        $cemitasPollo += $qty;
                        $cemitasPuerco += $qty;
                    } else {
                        if (strpos($descripcionStr, 'pollo') !== false) {
                            $cemitasPollo += $qty;
                        } elseif (strpos($descripcionStr, 'puerco') !== false || strpos($descripcionStr, 'cerdo') !== false || strpos($descripcionStr, 'milanesa') !== false) {
                            $cemitasPuerco += $qty;
                        }
                    }
                }

                $totalFormatted = number_format($pedido->costo ?? 0, 2);
                $mensajeWa = "*📦 NUEVO PEDIDO #{$pedido->id} - El bajon*\n\n" .
                             "• *Cliente:* {$clienteNombre}\n" .
                             "• *Artículo:* {$articuloNombre}\n" .
                             "• *Cantidad:* {$pedido->cantidad}\n" .
                             "• *Total:* \${$totalFormatted}\n" .
                             "• *Notas:* " . ($pedido->descripcion ?: 'Sin notas') . "\n\n" .
                             "_Enviado por El bajon_";

                // Enviar individual a ADMIN
                $sendToAdmin = true;
                if (Auth::user()->hasRole('admin') && !$esCemita) {
                    $sendToAdmin = false;
                }

                if ($sendToAdmin) {
                    // Web Push / Database Notification
                    $adminUsers = \App\Models\User::whereHas('roles', function($q) { $q->where('name', 'admin'); })->get();
                    \Illuminate\Support\Facades\Notification::send($adminUsers, new \App\Notifications\AppNotification(
                        "NUEVO PEDIDO #{$pedido->id} 📦", 
                        "{$clienteNombre} ordenó {$articuloNombre} ($" . $totalFormatted . ")",
                        route('pedidos.index')
                    ));

                    foreach ($adminPhones as $telAdmin) {
                        \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                            'numero' => $telAdmin, 'mensaje' => $mensajeWa, 'status' => 'pendiente', 'created_at' => now(), 'updated_at' => now()
                        ]);
                    }
                }

                // Enviar individual a COCINA solo si no es cemita
                if (!$esCemita) {
                    // Web Push / Database Notification
                    $cocinaUsers = \App\Models\User::whereHas('roles', function($q) { $q->where('name', 'cocina'); })->get();
                    \Illuminate\Support\Facades\Notification::send($cocinaUsers, new \App\Notifications\AppNotification(
                        "PREPARAR PEDIDO #{$pedido->id} 🧑‍🍳", 
                        "{$articuloNombre} para {$clienteNombre}",
                        route('pedidos.index')
                    ));

                    foreach ($cocinaPhones as $telCocina) {
                        \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                            'numero' => $telCocina, 'mensaje' => $mensajeWa, 'status' => 'pendiente', 'created_at' => now(), 'updated_at' => now()
                        ]);
                    }
                }

            } catch (\Exception $exWa) {
                \Illuminate\Support\Facades\Log::error("Error encolando WhatsApp de Nuevo Pedido #{$pedido->id}: " . $exWa->getMessage());
            }
        }

        // Enviar RESUMEN a COCINA
        if ($tieneCemitas && !empty($cocinaPhones)) {
            try {
                $mensajeResumen = "*🧑‍🍳 RESUMEN DE MILANESAS (NUEVO PEDIDO)*\n\n" .
                                  "• *Cliente:* {$clienteNombreResumen}\n" .
                                  "• *Pollo a preparar:* {$cemitasPollo}\n" .
                                  "• *Puerco a preparar:* {$cemitasPuerco}\n\n" .
                                  "_(Las milanesas necesarias para todo este pedido)_";
                
                foreach ($cocinaPhones as $telCocina) {
                    \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                        'numero' => $telCocina, 'mensaje' => $mensajeResumen, 'status' => 'pendiente', 'created_at' => now(), 'updated_at' => now()
                    ]);
                }
            } catch (\Exception $ex) {
                \Illuminate\Support\Facades\Log::error("Error enviando resumen a cocina: " . $ex->getMessage());
            }
        }

        return redirect()->route('pedidos.index')->with('success', 'Todos los pedidos fueron creados correctamente.');
    }

    public function edit($id)
    {
        $pedido = Pedido::with('venta')->find($id);
        if (!Auth::user()->hasRole('admin') && $pedido->user_id != Auth::id()) {
            abort(403, 'No tienes permiso para editar este pedido.');
        }

        $articulos = Articulo::all();
        $users  = User::orderBy('name', 'asc')->get();
        return view('pedidos.edit', compact('pedido', 'articulos', 'users'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'pedidos.*.id'           => 'nullable|exists:pedidos,id',
            'pedidos.*.articulo_id'  => 'required|exists:articulos,id',
            'pedidos.*.cantidad'     => 'required|integer|min:1',
            'pedidos.*.costo'        => 'required|numeric|min:1',
            'pedidos.*.descripcion'  => 'nullable|string',
            'pedidos.*.user_id'      => 'nullable|exists:users,id',
        ]);

        $userId = Auth::user()->hasRole('admin') ? $request->user_id : Auth::id();

        // Obtener teléfonos de admin y cocina
        $adminPhones = \Illuminate\Support\Facades\DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.name', 'admin')
            ->whereNotNull('users.telefono')
            ->where('users.telefono', '!=', '')
            ->pluck('users.telefono')
            ->map(function ($tel) { return preg_replace('/[^0-9]/', '', $tel); })
            ->map(function ($num) { return (strlen($num) == 10) ? '521' . $num : $num; })
            ->filter()->unique()->toArray();
        if (empty($adminPhones)) $adminPhones = ['5212222153410'];

        $cocinaPhones = \Illuminate\Support\Facades\DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.name', 'cocina')
            ->whereNotNull('users.telefono')
            ->where('users.telefono', '!=', '')
            ->pluck('users.telefono')
            ->map(function ($tel) { return preg_replace('/[^0-9]/', '', $tel); })
            ->map(function ($num) { return (strlen($num) == 10) ? '521' . $num : $num; })
            ->filter()->unique()->toArray();

        $cemitasPollo = 0;
        $cemitasPuerco = 0;
        $tieneCemitas = false;
        $clienteNombreResumen = 'Cliente';

        foreach ($request->pedidos as $p) {
            $total = $p['costo'] * $p['cantidad'];

            if (!empty($p['id'])) {
                $pedidoExistente = Pedido::find($p['id']);
                $pedidoExistente->update([
                    'articulo_id' => $p['articulo_id'],
                    'descripcion' => $p['descripcion'] ?? '',
                    'costo'       => $total,
                    'cantidad'    => $p['cantidad'],
                    'user_id'     => $p['user_id'] ?? $userId,
                ]);

                if ($pedidoExistente->venta) {
                    $pedidoExistente->venta()->update([
                        'articulo_id'  => $p['articulo_id'],
                        'cantidad'     => $p['cantidad'],
                        'precio_venta' => $p['costo'],
                        'total_venta'  => $total,
                        'user_id'     => $p['user_id'] ?? $userId,
                        'descripcion'  => $p['descripcion'] ?? '',
                    ]);
                }
                $pedidoParaMensaje = $pedidoExistente;
            } else {
                $venta = Venta::create([
                    'user_id'     => $p['user_id'] ?? $userId,
                    'articulo_id'  => $p['articulo_id'],
                    'cantidad'     => $p['cantidad'],
                    'precio_venta' => $p['costo'],
                    'total_venta'  => $total,
                    'descripcion'  => $p['descripcion'] ?? '',
                ]);

                $pedidoNuevo = Pedido::create([
                    'user_id'     => $p['user_id'] ?? $userId,
                    'articulo_id' => $p['articulo_id'],
                    'descripcion' => $p['descripcion'] ?? '',
                    'cantidad'    => $p['cantidad'],
                    'costo'       => $total,
                    'venta_id'    => $venta->id,
                ]);
                $pedidoParaMensaje = $pedidoNuevo;
            }

            $pedidoParaMensaje->load('articulo', 'user');

            try {
                $articuloNombre = $pedidoParaMensaje->articulo->nombre ?? 'N/A';
                $clienteNombre  = $pedidoParaMensaje->user->name ?? 'Cliente';
                $clienteNombreResumen = $clienteNombre;
                $descripcionStr = strtolower($articuloNombre . ' ' . ($pedidoParaMensaje->descripcion ?? ''));

                $esCemita = (strpos($descripcionStr, 'cemita') !== false);

                if ($esCemita) {
                    $tieneCemitas = true;
                    $qty = (int) $pedidoParaMensaje->cantidad;
                    if (strpos($descripcionStr, 'hawaiana') !== false || strpos($descripcionStr, 'cubana') !== false || strpos($descripcionStr, 'texana') !== false || strpos($descripcionStr, 'tejama') !== false) {
                        $cemitasPollo += $qty;
                        $cemitasPuerco += $qty;
                    } else {
                        if (strpos($descripcionStr, 'pollo') !== false) {
                            $cemitasPollo += $qty;
                        } elseif (strpos($descripcionStr, 'puerco') !== false || strpos($descripcionStr, 'cerdo') !== false || strpos($descripcionStr, 'milanesa') !== false) {
                            $cemitasPuerco += $qty;
                        }
                    }
                }

                $totalFormatted = number_format($pedidoParaMensaje->costo ?? 0, 2);
                $mensajeWa = "*🔄 ACTUALIZACIÓN DE PEDIDO #{$pedidoParaMensaje->id}*\n\n" .
                             "• *Cliente:* {$clienteNombre}\n" .
                             "• *Se modificó a:* {$articuloNombre}\n" .
                             "• *Cantidad:* {$pedidoParaMensaje->cantidad}\n" .
                             "• *Total modificado:* \${$totalFormatted}\n" .
                             "• *Notas:* " . ($pedidoParaMensaje->descripcion ?: 'Sin notas') . "\n\n" .
                             "_Enviado por El bajon_";

                $sendToAdmin = true;
                if (Auth::user()->hasRole('admin') && !$esCemita) {
                    $sendToAdmin = false;
                }

                if ($sendToAdmin) {
                    foreach ($adminPhones as $telAdmin) {
                        \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                            'numero' => $telAdmin, 'mensaje' => $mensajeWa, 'status' => 'pendiente', 'created_at' => now(), 'updated_at' => now()
                        ]);
                    }
                }

                if (!$esCemita) {
                    foreach ($cocinaPhones as $telCocina) {
                        \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                            'numero' => $telCocina, 'mensaje' => $mensajeWa, 'status' => 'pendiente', 'created_at' => now(), 'updated_at' => now()
                        ]);
                    }
                }
            } catch (\Exception $exWa) {
                \Illuminate\Support\Facades\Log::error("Error encolando WhatsApp de Actualización Pedido #{$pedidoParaMensaje->id}: " . $exWa->getMessage());
            }
        }

        // Enviar RESUMEN ACTUALIZADO a COCINA
        if ($tieneCemitas && !empty($cocinaPhones)) {
            try {
                $mensajeResumen = "*🧑‍🍳 ACTUALIZACIÓN DE RESUMEN DE MILANESAS*\n\n" .
                                  "• *Cliente:* {$clienteNombreResumen}\n" .
                                  "• *Nuevas Milanesas de Pollo:* {$cemitasPollo}\n" .
                                  "• *Nuevas Milanesas de Puerco:* {$cemitasPuerco}\n\n" .
                                  "_(Cantidades totales requeridas tras la actualización del pedido)_";
                
                foreach ($cocinaPhones as $telCocina) {
                    \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                        'numero' => $telCocina, 'mensaje' => $mensajeResumen, 'status' => 'pendiente', 'created_at' => now(), 'updated_at' => now()
                    ]);
                }
            } catch (\Exception $ex) {
                \Illuminate\Support\Facades\Log::error("Error enviando resumen de actualización a cocina: " . $ex->getMessage());
            }
        }

        return redirect()->route('pedidos.index')->with('success', 'Pedidos actualizados correctamente.');
    }

    public function destroy($id)
    {
        $pedido = Pedido::with('venta')->findOrFail($id);

        // Permisos
        if (!Auth::user()->hasRole('admin') && $pedido->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para eliminar este pedido.');
        }

        $venta = $pedido->venta;

        // Eliminar pedido
        $pedido->delete();

        // Si la venta ya no tiene pedidos, eliminarla
        if ($venta && $venta->pedidos()->count() === 0) {
            $venta->delete();
        }

        return redirect()
            ->route('pedidos.index')
            ->with('success', 'Pedido eliminado correctamente.');
    }
}
