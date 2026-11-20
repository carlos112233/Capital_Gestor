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

        $userId = Auth::user()->hasRole('admin') ? $request->user_id : Auth::id();
        $authUserId = (int) Auth::id();
        $isAdmin = Auth::user()->hasRole('admin');

        // Helper para formatear teléfonos mexicanos a formato WhatsApp (521XXXXXXXXXX)
        $formatPhone = function ($tel) {
            $num = preg_replace('/[^0-9]/', '', (string) $tel);
            if (strlen($num) === 10) return '521' . $num;
            if (strlen($num) === 12 && str_starts_with($num, '52')) return '521' . substr($num, 2);
            return $num;
        };

        // 1. Obtener teléfonos de admin y cocina
        $adminPhones = \Illuminate\Support\Facades\DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(roles.name)'), ['admin', 'administrador'])
            ->whereNotNull('users.telefono')
            ->where('users.telefono', '!=', '')
            ->pluck('users.telefono')
            ->map($formatPhone)
            ->filter()->unique()->toArray();
        if (empty($adminPhones)) $adminPhones = ['5212222153410'];

        $cocinaPhones = \Illuminate\Support\Facades\DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(roles.name)'), ['cocina'])
            ->whereNotNull('users.telefono')
            ->where('users.telefono', '!=', '')
            ->pluck('users.telefono')
            ->map($formatPhone)
            ->filter()->unique()->toArray();

        $articuloIds = collect($validated['pedidos'])
            ->pluck('articulo_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $validated,
            $articuloIds,
            $userId,
            $authUserId,
            $isAdmin,
            $adminPhones,
            $cocinaPhones
        ): void {
            // Bloqueo pesimista de los artículos involucrados en el pedido
            $articulosBloqueados = Articulo::whereIn('id', $articuloIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validar disponibilidad y stock acumulado por artículo antes de mutar inventario
            $cantidadesRequeridas = [];
            foreach ($validated['pedidos'] as $item) {
                $artId = (int) $item['articulo_id'];
                $cantidadesRequeridas[$artId] = ($cantidadesRequeridas[$artId] ?? 0) + (int) $item['cantidad'];
            }

            foreach ($cantidadesRequeridas as $artId => $cantidadTotal) {
                /** @var Articulo|null $articulo */
                $articulo = $articulosBloqueados->get($artId);
                if (!$articulo || !$articulo->disponible) {
                    $nombreArt = $articulo->nombre ?? "#{$artId}";
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'pedidos' => "El artículo '{$nombreArt}' no se encuentra disponible.",
                    ]);
                }

                if ((int) $articulo->stock < $cantidadTotal) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'pedidos' => "Stock insuficiente para el artículo '{$articulo->nombre}'. Disponible: {$articulo->stock}",
                    ]);
                }
            }

            // Variables para resumen de notificaciones post-commit
            $cemitasPollo = 0;
            $cemitasPuerco = 0;
            $tieneCemitas = false;
            $clienteNombreResumen = 'Cliente';
            $itemsParaCocina = [];
            $itemsParaAdmin = [];
            $pedidosCreados = [];
            $mensajesIndividualesAdmin = [];
            $notificacionesCocinaWebPush = [];
            $totalPedidoGlobal = 0.0;

            foreach ($validated['pedidos'] as $p) {
                $artId = (int) $p['articulo_id'];
                /** @var Articulo $articulo */
                $articulo = $articulosBloqueados->get($artId);
                $cantidadPedido = (int) $p['cantidad'];

                // Descontar stock dentro del bloqueo pesimista
                $articulo->decrement('stock', $cantidadPedido);
                if ($articulo->stock <= 0) {
                    $articulo->disponible = false;
                    $articulo->save();
                }

                // Autoridad estricta del precio maestro desde base de datos
                $precioUnitario = (float) $articulo->precio;
                $total = $precioUnitario * $cantidadPedido;
                $totalPedidoGlobal += $total;
                $targetUserId = $p['user_id'] ?? $userId;

                $venta = Venta::create([
                    'user_id'      => $targetUserId,
                    'comprador_id' => $authUserId,
                    'articulo_id'  => $artId,
                    'cantidad'     => $cantidadPedido,
                    'precio_venta' => $precioUnitario,
                    'total_venta'  => $total,
                    'descripcion'  => $p['descripcion'] ?? '',
                ]);

                $pedido = Pedido::create([
                    'user_id'     => $targetUserId,
                    'articulo_id' => $artId,
                    'descripcion' => $p['descripcion'] ?? '',
                    'costo'       => $total,
                    'venta_id'    => $venta->id,
                    'cantidad'    => $cantidadPedido,
                ]);

                $pedido->load('articulo', 'user');
                $pedidosCreados[] = $pedido;

                $articuloNombre = $pedido->articulo->nombre ?? 'N/A';
                $clienteNombre = $pedido->user->name ?? 'Cliente';
                $clienteNombreResumen = $clienteNombre;
                $descripcionStr = mb_strtolower($articuloNombre . ' ' . ($pedido->descripcion ?? ''));

                $esCemita = (str_contains($descripcionStr, 'cemita') || str_contains($descripcionStr, 'milanesa'));

                if ($esCemita) {
                    $tieneCemitas = true;
                    if (str_contains($descripcionStr, 'hawaiana') || str_contains($descripcionStr, 'cubana') || str_contains($descripcionStr, 'texana') || str_contains($descripcionStr, 'tejama')) {
                        $cemitasPollo += $cantidadPedido;
                        $cemitasPuerco += $cantidadPedido;
                    } else {
                        if (str_contains($descripcionStr, 'pollo')) {
                            $cemitasPollo += $cantidadPedido;
                        } elseif (str_contains($descripcionStr, 'puerco') || str_contains($descripcionStr, 'cerdo') || str_contains($descripcionStr, 'milanesa')) {
                            $cemitasPuerco += $cantidadPedido;
                        }
                    }
                }

                $totalFormatted = number_format((float) ($pedido->costo ?? 0), 2);
                $notasStr = $pedido->descripcion ?: 'Sin notas';
                $lineaItem = "• *{$articuloNombre}* (x{$pedido->cantidad}) = \${$totalFormatted}\n  _Nota:_ {$notasStr}";

                if (!$esCemita) {
                    $itemsParaCocina[] = "• *{$articuloNombre}* (x{$pedido->cantidad})\n  _Nota:_ {$notasStr}";
                    $notificacionesCocinaWebPush[] = [
                        'pedido_id'       => $pedido->id,
                        'articulo_nombre' => $articuloNombre,
                        'cliente_nombre'  => $clienteNombre,
                    ];
                }

                $itemsParaAdmin[] = $lineaItem;

                if ($isAdmin && $esCemita) {
                    $appName = \App\Models\AppSetting::getSettings()->app_name ?? 'El Bajón';
                    $mensajesIndividualesAdmin[] = "*📦 NUEVO PEDIDO DE CEMITA #{$pedido->id} - {$appName}*\n\n" .
                        "• *Cliente:* {$clienteNombre}\n" .
                        "• *Artículo:* {$articuloNombre}\n" .
                        "• *Cantidad:* {$pedido->cantidad}\n" .
                        "• *Total:* \${$totalFormatted}\n" .
                        "• *Notas:* {$notasStr}\n\n" .
                        "_Enviado por {$appName}_";
                }
            }

            // Desacoplamiento quirúrgico de todas las notificaciones externas tras consolidar el commit
            \Illuminate\Support\Facades\DB::afterCommit(function () use (
                $isAdmin,
                $adminPhones,
                $cocinaPhones,
                $mensajesIndividualesAdmin,
                $notificacionesCocinaWebPush,
                $itemsParaAdmin,
                $itemsParaCocina,
                $tieneCemitas,
                $cemitasPollo,
                $cemitasPuerco,
                $clienteNombreResumen,
                $totalPedidoGlobal,
                $pedidosCreados
            ): void {
                $waService = app(\App\Services\WhatsAppNotificationService::class);

                // Notificaciones individuales de cemitas cuando pide Admin
                foreach ($mensajesIndividualesAdmin as $mensajeWaIndiv) {
                    foreach ($adminPhones as $telAdmin) {
                        try {
                            $waService->enqueuePayload([
                                'numero'     => $telAdmin,
                                'mensaje'    => $mensajeWaIndiv,
                                'status'     => 'pendiente',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } catch (\Throwable $exWa) {
                            \Illuminate\Support\Facades\Log::error("Error encolando WhatsApp individual de cemita: " . $exWa->getMessage());
                        }
                    }
                }

                // Notificaciones WebPush a Cocina por cada platillo
                if (!empty($notificacionesCocinaWebPush)) {
                    try {
                        $cocinaUsers = \App\Models\User::whereHas('roles', function ($q) {
                            $q->where('name', 'cocina');
                        })->get();

                        foreach ($notificacionesCocinaWebPush as $notifCocina) {
                            \Illuminate\Support\Facades\Notification::send($cocinaUsers, new \App\Notifications\AppNotification(
                                "PREPARAR PEDIDO #{$notifCocina['pedido_id']} 🧑‍🍳",
                                "{$notifCocina['articulo_nombre']} para {$notifCocina['cliente_nombre']}",
                                route('pedidos.index')
                            ));
                        }
                    } catch (\Throwable $exCocinaPush) {
                        \Illuminate\Support\Facades\Log::error("Error enviando WebPush a cocina: " . $exCocinaPush->getMessage());
                    }
                }

                // 1. AL ADMIN: REGLA 1: Si es un usuario normal, se le avisa cualquier pedido que realizó el usuario (Mensaje Consolidado).
                if (!$isAdmin && count($itemsParaAdmin) > 0 && !empty($adminPhones)) {
                    try {
                        $appName = \App\Models\AppSetting::getSettings()->app_name ?? 'El Bajón';
                        $totalMonto = number_format($totalPedidoGlobal, 2);
                        $totalPiezasPedidos = array_sum(array_map(fn ($p) => (int) ($p->cantidad ?? 1), $pedidosCreados));
                        $mensajeResumenAdmin = "*📦 NUEVO PEDIDO MÚLTIPLE - {$appName}*\n\n" .
                            "• *Cliente:* {$clienteNombreResumen}\n" .
                            "• *Total de productos:* {$totalPiezasPedidos} unidad(es)\n\n" .
                            "*PRODUCTOS:*\n" .
                            implode("\n", $itemsParaAdmin) . "\n\n" .
                            "*TOTAL DEL PEDIDO:* \${$totalMonto}\n\n" .
                            "_Enviado por {$appName}_";

                        foreach ($adminPhones as $telAdmin) {
                            $waService->enqueuePayload([
                                'numero'     => $telAdmin,
                                'mensaje'    => $mensajeResumenAdmin,
                                'status'     => 'pendiente',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } catch (\Throwable $exAdminWa) {
                        \Illuminate\Support\Facades\Log::error("Error enviando resumen WhatsApp a admin: " . $exAdminWa->getMessage());
                    }
                }

                // 2. A COCINA: REGLA 4: Siempre se envía a cocina para realizar el pedido (Admin o Usuario normal).
                if ((!empty($itemsParaCocina) || $tieneCemitas) && !empty($cocinaPhones)) {
                    try {
                        $appName = \App\Models\AppSetting::getSettings()->app_name ?? 'El Bajón';
                        $mensajeResumenCocina = "*🧑‍🍳 NUEVA ORDEN COMPLETA - {$appName}*\n\n" .
                            "• *Cliente:* {$clienteNombreResumen}\n\n";

                        if (!empty($itemsParaCocina)) {
                            $mensajeResumenCocina .= "*PLATILLOS:*\n" . implode("\n------------------------------------------\n", $itemsParaCocina) . "\n\n";
                        }

                        if ($tieneCemitas) {
                            $mensajeResumenCocina .= "------------------------------------------\n" .
                                "*RESUMEN DE MILANESAS:*\n" .
                                "• Pollo a preparar: {$cemitasPollo}\n" .
                                "• Puerco a preparar: {$cemitasPuerco}\n\n" .
                                "_(Las milanesas necesarias para todo este pedido)_\n\n";
                        }

                        $mensajeResumenCocina .= "_Enviado por {$appName}_";

                        foreach ($cocinaPhones as $telCocina) {
                            $waService->enqueuePayload([
                                'numero'     => $telCocina,
                                'mensaje'    => $mensajeResumenCocina,
                                'status'     => 'pendiente',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\Log::error("Error enviando resumen completo a cocina: " . $ex->getMessage());
                    }
                }

                // 3. Enviar Notificación Push al Administrador al crear pedido
                if (!$isAdmin) {
                    try {
                        $totalPiezasPedidos = array_sum(array_map(fn ($p) => (int) ($p->cantidad ?? 1), $pedidosCreados));
                        $itemsNombres = array_map(function ($p) {
                            $artNom = $p->articulo->nombre ?? 'Artículo';
                            $cant = (int) ($p->cantidad ?? 1);
                            return "{$cant}x {$artNom}";
                        }, $pedidosCreados);
                        $listaPedidos = \Illuminate\Support\Str::limit(implode(', ', $itemsNombres), 180, '...');
                        $totalMonto = number_format($totalPedidoGlobal, 2);

                        \App\Services\PushNotificationService::notifyAdmins(
                            "Nuevo Pedido Recibido 📦",
                            "El cliente {$clienteNombreResumen} realizó un pedido de {$totalPiezasPedidos} producto(s) por un total de \${$totalMonto}: {$listaPedidos}.",
                            route('pedidos.index')
                        );
                    } catch (\Throwable $exPushAdmin) {
                        \Illuminate\Support\Facades\Log::error("Error enviando Push de nuevo pedido a admins: " . $exPushAdmin->getMessage());
                    }
                }
            });
        });

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
                    foreach ($adminPhones as $telAdmin) {
                        app(\App\Services\WhatsAppNotificationService::class)->enqueuePayload([
                            'numero' => $telAdmin, 'mensaje' => $mensajeWa, 'status' => 'pendiente', 'created_at' => now(), 'updated_at' => now()
                        ]);
                    }
                }

                if (!$esCemita) {
                    foreach ($cocinaPhones as $telCocina) {
                        app(\App\Services\WhatsAppNotificationService::class)->enqueuePayload([
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
                    app(\App\Services\WhatsAppNotificationService::class)->enqueuePayload([
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
