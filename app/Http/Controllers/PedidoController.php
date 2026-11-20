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

        // Optimización de velocidad: restringir a los últimos 15 días sólo cuando no hay búsqueda activa
        if (!$request->filled('q')) {
            $query->where('created_at', '>=', now()->subDays(15));
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
        // Cargar únicamente columnas necesarias para el select del modal (reduce uso de memoria y acelera la consulta SQL)
        $query = Articulo::select('id', 'nombre', 'precio')->orderBy('nombre', 'asc');
        if (!Auth::user()->hasRole('admin')) {
            $query->where('disponible', true);
        }
        $articulos = $query->get();
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'cliente');
        })->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['admin', 'vendedor', 'cocina']);
        })->orderBy('name')->get(['id', 'name']);

        return view('pedidos.index', compact('pedidos', 'articulos', 'users'));
    }


    public function create()
    {
        $query = Articulo::orderBy('nombre', 'asc');
        if (!Auth::user()->hasRole('admin')) {
            $query->where('disponible', true);
        }
        $articulos = $query->get();
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'cliente');
        })->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['admin', 'vendedor', 'cocina']);
        })->orderBy('name')->get(['id', 'name']);
        $articuloId = null; // o algún valor por defecto, por ejemplo el primer artículo
        return view('pedidos.create', compact('articulos', 'articuloId', 'users'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'pedidos'               => 'required|array|min:1',
            'pedidos.*.articulo_id' => 'required|exists:articulos,id',
            'pedidos.*.cantidad'    => 'required|integer|min:1',
            'pedidos.*.descripcion' => 'nullable|string',
            'pedidos.*.user_id'     => 'nullable|exists:users,id',
        ]);

        $defaultUserId = Auth::user()->hasRole('admin') && $request->filled('user_id')
            ? (int) $request->input('user_id')
            : (int) Auth::id();

        app(\App\Actions\Pos\RegistrarPedidoAction::class)->execute(
            $validated['pedidos'],
            Auth::user(),
            $defaultUserId
        );

        return redirect()->route('pedidos.index')->with('success', 'Todos los pedidos fueron creados correctamente.');
    }

    public function edit($id)
    {
        $pedido = Pedido::with('venta')->find($id);
        if (!Auth::user()->hasRole('admin') && $pedido->user_id != Auth::id()) {
            abort(403, 'No tienes permiso para editar este pedido.');
        }

        $articulos = Articulo::all();
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'cliente');
        })->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['admin', 'vendedor', 'cocina']);
        })->orderBy('name')->get(['id', 'name']);
        return view('pedidos.edit', compact('pedido', 'articulos', 'users'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'pedidos'               => 'required|array|min:1',
            'pedidos.*.id'           => 'nullable|exists:pedidos,id',
            'pedidos.*.articulo_id'  => 'required|exists:articulos,id',
            'pedidos.*.cantidad'     => 'required|integer|min:1',
            'pedidos.*.descripcion'  => 'nullable|string',
            'pedidos.*.user_id'      => 'nullable|exists:users,id',
        ]);

        $userId = Auth::user()->hasRole('admin') ? $request->user_id : Auth::id();
        $waService = app(\App\Services\WhatsAppNotificationService::class);
        $adminPhones = $waService->getAdminPhones();
        $cocinaPhones = $waService->getCocinaPhones();

        $cemitasPollo = 0;
        $cemitasPuerco = 0;
        $tieneCemitas = false;
        $clienteNombreResumen = 'Cliente';

        foreach ($validated['pedidos'] as $p) {
            $articulo = Articulo::findOrFail($p['articulo_id']);
            $precioUnitario = (float) $articulo->precio;
            $total = $precioUnitario * (int) $p['cantidad'];

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
                        'precio_venta' => $precioUnitario,
                        'total_venta'  => $total,
                        'user_id'     => $p['user_id'] ?? $userId,
                        'descripcion'  => $p['descripcion'] ?? '',
                    ]);
                }
                $pedidoParaMensaje = $pedidoExistente;
            } else {
                $venta = Venta::create([
                    'user_id'      => $p['user_id'] ?? $userId,
                    'comprador_id' => Auth::id(),
                    'articulo_id'  => $p['articulo_id'],
                    'cantidad'     => $p['cantidad'],
                    'precio_venta' => $precioUnitario,
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
                $articuloObj = $pedidoParaMensaje->articulo;
                $articuloNombre = $articuloObj->nombre ?? 'N/A';
                $clienteNombre  = $pedidoParaMensaje->user->name ?? 'Cliente';
                $clienteNombreResumen = $clienteNombre;

                $esCemita = $articuloObj ? $articuloObj->esCemitaOMilanesa() : false;

                if ($esCemita && $articuloObj) {
                    $tieneCemitas = true;
                    $conteo = $articuloObj->conteoMilanesas((int) $pedidoParaMensaje->cantidad);
                    $cemitasPollo += $conteo['pollo'];
                    $cemitasPuerco += $conteo['puerco'];
                }

                $totalFormatted = number_format($pedidoParaMensaje->costo ?? 0, 2);
                $appName = \App\Models\AppSetting::getSettings()->app_name ?? 'El Bajón';
                $mensajeWa = "*🔄 ACTUALIZACIÓN DE PEDIDO #{$pedidoParaMensaje->id}*\n\n" .
                             "• *Cliente:* {$clienteNombre}\n" .
                             "• *Se modificó a:* {$articuloNombre}\n" .
                             "• *Cantidad:* {$pedidoParaMensaje->cantidad}\n" .
                             "• *Total modificado:* \${$totalFormatted}\n" .
                             "• *Notas:* " . ($pedidoParaMensaje->descripcion ?: 'Sin notas') . "\n\n" .
                             "_Enviado por {$appName}_";

                $sendToAdmin = true;
                if (Auth::user()->hasRole('admin') && !$esCemita) {
                    $sendToAdmin = false;
                }

                if ($sendToAdmin) {
                    $waService->enqueueToMultiple($adminPhones, $mensajeWa);
                }

                if ($articuloObj && $articuloObj->requierePreparacionPlatilloCocina()) {
                    $waService->enqueueToMultiple($cocinaPhones, $mensajeWa);
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
                
                $waService->enqueueToMultiple($cocinaPhones, $mensajeResumen);
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

    /**
     * Actualiza el estado de un pedido en 1 solo clic y notifica al cliente vía Push.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:realizado,en_preparacion,entregado,En preparación,Entregado',
        ]);

        $pedido = Pedido::with('user')->findOrFail($id);
        $rawStatus = strtolower($request->status);

        $statusNormalized = match (true) {
            str_contains($rawStatus, 'preparac') => 'en_preparacion',
            str_contains($rawStatus, 'entregad') => 'entregado',
            default => 'realizado',
        };

        $pedido->status = $statusNormalized;
        $pedido->save();

        // Notificar Push al cliente
        if ($pedido->user) {
            if ($statusNormalized === 'en_preparacion') {
                \App\Services\PushNotificationService::notifyUser(
                    $pedido->user,
                    "Estado de Pedido ⏳",
                    "Su pedido #{$pedido->id} se encuentra en proceso de preparación. Gracias por su preferencia.",
                    route('pedidos.index')
                );
            } elseif ($statusNormalized === 'entregado') {
                \App\Services\PushNotificationService::notifyUser(
                    $pedido->user,
                    "Pedido Entregado 🟢",
                    "Su pedido #{$pedido->id} ha sido entregado exitosamente. ¡Gracias por su preferencia!",
                    route('pedidos.index')
                );
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Estado del pedido actualizado correctamente.',
                'status' => $statusNormalized,
            ]);
        }

        return back()->with('success', 'Estado del pedido actualizado correctamente.');
    }
}
