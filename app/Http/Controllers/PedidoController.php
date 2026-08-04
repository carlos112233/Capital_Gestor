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
        $articulos = Articulo::orderBy('nombre', 'asc')->get();
        $users = User::orderBy('name', 'asc')->get();

        return view('pedidos.index', compact('pedidos', 'articulos', 'users'));
    }


    public function create()
    {
        $articulos = Articulo::orderBy('nombre', 'asc')->get();
        $users = User::orderBy('name', 'asc')->get();
        $articuloId = null; // o algún valor por defecto, por ejemplo el primer artículo
        return view('pedidos.create', compact('articulos', 'articuloId', 'users'));
    }


    public function store(Request $request)
    { //dd($request->all()); 
        $validated = $request->validate([
            'pedidos.*.articulo_id' => 'required|exists:articulos,id',
            'pedidos.*.cantidad'    => 'required|integer|min:1',
            'pedidos.*.costo'       => 'required|numeric|min:1',
            'pedidos.*.descripcion' => 'nullable|string',
            'pedidos.*.user_id'      => 'nullable|exists:users,id',
        ]);

        $userId = Auth::user()->hasRole('admin') ? $request->user_id : Auth::id();

        foreach ($request->pedidos as $p) {
            $total = $p['costo'] * $p['cantidad'];

            // Crear la venta
            $venta = Venta::create([
                'user_id'     => $p['user_id'],
                'articulo_id'  => $p['articulo_id'],
                'cantidad'     => $p['cantidad'],
                'precio_venta' => $p['costo'],
                'total_venta'  => $total,
                'descripcion'  => $p['descripcion'] ?? '',

            ]);

            // Crear el pedido
            $pedido =  Pedido::create([
                'user_id'     => $p['user_id'],
                'articulo_id' => $p['articulo_id'],
                'descripcion' => $p['descripcion'] ?? '',
                'costo'       => $total,
                'venta_id'    => $venta->id,
                'cantidad'    => $p['cantidad'],
            ]);

            // 1. Envío de notificación por WhatsApp al Administrador mediante wa-motor
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
                    $adminPhones = ['5212222153410'];
                }

                $articuloNombre = $pedido->articulo->nombre ?? 'N/A';
                $clienteNombre  = $pedido->user->name ?? 'Cliente';
                $totalFormatted = number_format($pedido->costo ?? 0, 2);
                $mensajeWa = "*📦 NUEVO PEDIDO #{$pedido->id} - El rico bajon*\n\n" .
                             "• *Cliente:* {$clienteNombre}\n" .
                             "• *Artículo:* {$articuloNombre}\n" .
                             "• *Cantidad:* {$pedido->cantidad}\n" .
                             "• *Total:* \${$totalFormatted}\n" .
                             "• *Notas:* " . ($pedido->descripcion ?: 'Sin notas') . "\n\n" .
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
                \Illuminate\Support\Facades\Log::info("Notificación WhatsApp de Nuevo Pedido #{$pedido->id} encolada para admins: " . implode(', ', $adminPhones));
            } catch (\Exception $exWa) {
                \Illuminate\Support\Facades\Log::error("Error encolando WhatsApp de Nuevo Pedido #{$pedido->id}: " . $exWa->getMessage());
            }

            // 2. Envío por Correo Electrónico (SMTP o Respaldo HTTPS)
            try {
                Notification::route('mail', 'gestorcapital.0925@gmail.com')
                    ->notify(new \App\Notifications\NuevoPedidoNotification($pedido));
                \Illuminate\Support\Facades\Log::info("Correo enviado exitosamente para el pedido #" . $pedido->id . " a gestorcapital.0925@gmail.com via SMTP");
            } catch (\Exception $e) {
                // Si falla SMTP (p. ej. por bloqueo de puertos de DigitalOcean), intentamos envío por API HTTPS (puerto 443) con FormSubmit
                \Illuminate\Support\Facades\Log::warning("SMTP falló para el pedido #" . $pedido->id . " (" . $e->getMessage() . "). Intentando envío alternativo por API HTTPS...");

                try {
                    $articuloNombre = $pedido->articulo->nombre ?? 'N/A';
                    $clienteNombre  = $pedido->user->name ?? 'Cliente';
                    $totalFormatted = number_format($pedido->costo ?? 0, 2);
                    $cuerpoCorreo   = "Nuevo Pedido #{$pedido->id} creado en El rico bajon.\n\n" .
                                      "• Cliente: {$clienteNombre}\n" .
                                      "• Artículo: {$articuloNombre}\n" .
                                      "• Cantidad: {$pedido->cantidad}\n" .
                                      "• Total: \${$totalFormatted}\n" .
                                      "• Descripción/Notas: {$pedido->descripcion}\n\n" .
                                      "Enviado desde el sistema CRM El rico bajon.";

                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                        'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Origin'       => 'https://elbajon.duckdns.org',
                        'Referer'      => 'https://elbajon.duckdns.org/pedidos',
                    ])->post('https://formsubmit.co/ajax/gestorcapital.0925@gmail.com', [
                        '_subject' => "Nuevo Pedido #{$pedido->id} - El rico bajon",
                        'mensaje'  => $cuerpoCorreo,
                        'pedido_id'=> $pedido->id,
                        'cliente'  => $clienteNombre,
                        'total'    => "$" . $totalFormatted,
                    ]);

                    if ($response->successful()) {
                        \Illuminate\Support\Facades\Log::info("Correo alternativo HTTPS (FormSubmit) enviado exitosamente para pedido #" . $pedido->id);
                    } else {
                        \Illuminate\Support\Facades\Log::error("Fallo envío alternativo HTTPS (FormSubmit) para pedido #" . $pedido->id . ": " . $response->body());
                    }
                } catch (\Exception $exHttp) {
                    \Illuminate\Support\Facades\Log::error("Error general enviando correo alternativo HTTPS del pedido #" . $pedido->id . ": " . $exHttp->getMessage());
                }
            }
        }

        return redirect()->route('pedidos.index')->with('success', 'Todos los pedidos fueron creados correctamente.');
    }

    public function edit($id)
    {
        // Obtener el pedido con su venta
        $pedido = Pedido::with('venta')->find($id);
        // Verificar permisos: si no es admin, solo puede editar sus propios pedidos
        if (!Auth::user()->hasRole('admin') && $pedido->user_id != Auth::id()) {
            abort(403, 'No tienes permiso para editar este pedido.');
        }

        $articulos = Articulo::all();
        $users  = User::orderBy('name', 'asc')->get(); // si admin, puede cambiar el usuario

        return view('pedidos.edit', compact('pedido', 'articulos', 'users'));
    }

    public function update(Request $request, $id)
    {    //dd($request->all()); 

        $validated = $request->validate([
            'pedidos.*.id'           => 'nullable|exists:pedidos,id', // Si se quiere actualizar existentes
            'pedidos.*.articulo_id'  => 'required|exists:articulos,id',
            'pedidos.*.cantidad'     => 'required|integer|min:1',
            'pedidos.*.costo'        => 'required|numeric|min:1',
            'pedidos.*.descripcion'  => 'nullable|string',
            'pedidos.*.user_id'      => 'nullable|exists:users,id',
        ]);

        $userId = Auth::user()->hasRole('admin') ? $request->user_id : Auth::id();

        foreach ($request->pedidos as $p) {
            $total = $p['costo'] * $p['cantidad'];

            if (!empty($p['id'])) {
                // Actualizar pedido y su venta existente
                $pedidoExistente = Pedido::find($p['id']);
                $pedidoExistente->update([
                    'articulo_id' => $p['articulo_id'],
                    'descripcion' => $p['descripcion'] ?? '',
                    'costo'       => $total,
                    'cantidad'    => $p['cantidad'],
                    'user_id'     => $p['user_id'],
                ]);

                $pedidoExistente->venta()->update([
                    'articulo_id'  => $p['articulo_id'],
                    'cantidad'     => $p['cantidad'],
                    'precio_venta' => $p['costo'],
                    'total_venta'  => $total,
                    'user_id'     => $p['user_id'],
                    'descripcion'  => $p['descripcion'] ?? '',
                ]);
            } else {
                // Crear nueva venta y pedido
                $venta = Venta::create([
                    'user_id'     => $p['user_id'],
                    'articulo_id'  => $p['articulo_id'],
                    'cantidad'     => $p['cantidad'],
                    'precio_venta' => $p['costo'],
                    'total_venta'  => $total,
                    'descripcion'  => $p['descripcion'] ?? '',
                ]);

                Pedido::create([
                    'user_id'     => $p['user_id'],
                    'articulo_id' => $p['articulo_id'],
                    'descripcion' => $p['descripcion'] ?? '',
                    'cantidad'    => $p['cantidad'],
                    'costo'       => $total,
                    'venta_id'    => $venta->id,
                ]);
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
