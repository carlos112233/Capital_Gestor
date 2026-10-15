<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\User;
use App\Models\Articulo;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class VentaController extends Controller
{
    /**
     * Muestra el listado de ventas.
     */
    public function index(Request $request): View|string
    {
        $user = Auth::user();
        $quinceDiasAtras = Carbon::now()->subDays(15);

        $ventasQuery = Venta::with([
            'user:id,name',
            'comprador:id,name',
            'articulo:id,nombre,precio',
        ])->latest();

        if ($user->hasRole('vendedor')) {
            $ventasQuery->where('comprador_id', $user->id);
        } elseif (!$user->hasRole('admin')) {
            $ventasQuery->where('user_id', $user->id);
        }

        if ($request->filled('q')) {
            $search = (string) $request->input('q');
            $ventasQuery->whereHas('user', function ($query) use ($search): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        } else {
            $ventasQuery->where('created_at', '>=', $quinceDiasAtras);
        }

        $ventas = $ventasQuery->paginate(25)->withQueryString();

        $articulos = Articulo::select('id', 'nombre', 'precio', 'stock')
            ->comerciales()
            ->where('stock', '>', 0)
            ->orderBy('nombre', 'asc')
            ->get();
        $clientes = User::select('id', 'name')->orderBy('name', 'asc')->get();

        if ($request->ajax()) {
            return view('ventas._tabla', compact('ventas', 'articulos', 'clientes'))->render();
        }
        return view('ventas.index', compact('ventas', 'articulos', 'clientes'));
    }

    /**
     * Muestra el formulario para crear una venta.
     */
    public function create(Request $request): View
    {
        $articulos = Articulo::where('stock', '>', 0)
            ->whereNotIn('nombre', ['Pago saldado', 'saldo', 'Saldo', 'Abono', 'abono'])
            ->orderBy('nombre', 'asc')
            ->get();
        $clientes = User::orderBy('name', 'asc')->get();
        $articuloId = $request->get('articulo_id');

        return view('ventas.create', compact('articulos', 'clientes', 'articuloId'));
    }

    /**
     * Almacena una nueva venta en la base de datos con bloqueo pesimista y notificación asíncrona post-commit.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'articulo_id'  => 'required|exists:articulos,id',
            'cantidad'     => 'required|integer|min:1',
            'cliente_id'   => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion'  => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($validated, $request): RedirectResponse {
                // 1. Bloqueo Pesimista (X-Lock en la fila del inventario)
                $articulo = Articulo::lockForUpdate()->findOrFail($validated['articulo_id']);
                $cantidadVenta = (int) $validated['cantidad'];

                // 2. Validación defensiva de stock en tiempo real
                if ($articulo->stock < $cantidadVenta) {
                    throw ValidationException::withMessages([
                        'cantidad' => "Stock insuficiente. Disponible: {$articulo->stock}",
                    ]);
                }

                // 3. Resolución de identidad del comprador
                $finalUserId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
                    ? (int) $validated['cliente_id']
                    : (int) Auth::id();

                // 4. Ajuste atómico de stock e inventario
                $articulo->decrement('stock', $cantidadVenta);
                if ($articulo->stock <= 0) {
                    $articulo->disponible = false;
                    $articulo->save();
                }

                // 5. Cálculo exacto y registro de venta
                $totalVenta = ((float) $validated['precio_venta']) * $cantidadVenta;

                $venta = $articulo->ventas()->create([
                    'user_id'      => $finalUserId,
                    'comprador_id' => (int) Auth::id(),
                    'cantidad'     => $cantidadVenta,
                    'precio_venta' => $validated['precio_venta'],
                    'total_venta'  => $totalVenta,
                    'descripcion'  => $validated['descripcion'] ?? null,
                ]);

                // 6. Vinculación a pedido si aplica
                if ($request->filled('pedido_id')) {
                    $pedido = Pedido::find($request->input('pedido_id'));
                    if ($pedido) {
                        $pedido->update(['venta_id' => $venta->id]);
                    }
                }

                // 7. DESACOPLAMIENTO QUIRÚRGICO DE NOTIFICACIONES EXTERNAS (DB::afterCommit)
                // Se garantiza que el X-Lock de MySQL se libera INMEDIATAMENTE al hacer commit.
                // Si la red externa de WhatsApp demora o falla, la transacción ya fue consolidada y el stock liberado.
                if (!Auth::user()->hasRole('admin')) {
                    DB::afterCommit(function () use ($venta): void {
                        try {
                            Venta::notificarAdminWhatsApp($venta);
                        } catch (\Throwable $eNotification) {
                            Log::error("Fallo no bloqueante al notificar venta #{$venta->id} por WhatsApp: " . $eNotification->getMessage(), [
                                'exception' => $eNotification,
                                'venta_id'  => $venta->id,
                            ]);
                        }
                    });
                }

                if ($request->filled('redirect_to')) {
                    return redirect($request->input('redirect_to'))->with('success', '¡Venta registrada con éxito!');
                }

                if ($request->header('referer') && str_contains($request->header('referer'), 'catalogo')) {
                    return redirect()->route('catalogo.index')->with('success', '¡Venta registrada con éxito!');
                }

                return redirect()->route('ventas.index')->with('success', '¡Venta registrada con éxito!');
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::critical("Error transaccional en Store Venta: " . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => Auth::id(),
                'payload'   => $validated,
            ]);
            return redirect()->back()->with('error', 'Error en base de datos al registrar la venta. No se aplicaron cargos ni descuentos.');
        }
    }

    /**
     * Almacena múltiples ventas en una sola transacción atómica con desacoplamiento post-commit.
     */
    public function storeMultiple(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ventas'               => 'required|array|min:1',
            'ventas.*.articulo_id' => 'required|exists:articulos,id',
            'ventas.*.cantidad'    => 'required|integer|min:1',
            'ventas.*.precio'      => 'required|numeric|min:0',
            'ventas.*.user_id'     => 'nullable|exists:users,id',
            'ventas.*.descripcion' => 'nullable|string',
            'redirect_to'          => 'nullable|string'
        ]);

        try {
            return DB::transaction(function () use ($validated, $request): RedirectResponse {
                $ventasData = $validated['ventas'];
                $isAdmin = Auth::user()->hasRole('admin');
                $authUserId = (int) Auth::id();
                
                $ventasCreadasParaResumen = [];
                $totalCarrito = 0.0;

                foreach ($ventasData as $ventaData) {
                    $articulo = Articulo::lockForUpdate()->findOrFail($ventaData['articulo_id']);
                    $cantidadVenta = (int) $ventaData['cantidad'];

                    if ($articulo->stock < $cantidadVenta) {
                        throw ValidationException::withMessages([
                            'ventas' => "Stock insuficiente para el artículo '{$articulo->nombre}'. Disponible: {$articulo->stock}",
                        ]);
                    }

                    $finalUserId = ($isAdmin && !empty($ventaData['user_id'])) ? (int) $ventaData['user_id'] : $authUserId;

                    $articulo->decrement('stock', $cantidadVenta);
                    if ($articulo->stock <= 0) {
                        $articulo->disponible = false;
                        $articulo->save();
                    }

                    $totalVenta = ((float) $ventaData['precio']) * $cantidadVenta;
                    $totalCarrito += $totalVenta;

                    $venta = $articulo->ventas()->create([
                        'user_id'      => $finalUserId,
                        'comprador_id' => $authUserId,
                        'cantidad'     => $cantidadVenta,
                        'precio_venta' => $ventaData['precio'],
                        'total_venta'  => $totalVenta,
                        'descripcion'  => $ventaData['descripcion'] ?? null,
                    ]);

                    $ventasCreadasParaResumen[] = $venta;
                }

                // Desacoplar la notificación consolidada del carrito fuera del bloqueo pesimista
                if (!$isAdmin && count($ventasCreadasParaResumen) > 0) {
                    DB::afterCommit(function () use ($ventasCreadasParaResumen, $totalCarrito): void {
                        try {
                            Venta::notificarCarritoAdminWhatsApp($ventasCreadasParaResumen, $totalCarrito);
                        } catch (\Throwable $eNotification) {
                            Log::error("Fallo no bloqueante al notificar carrito por WhatsApp: " . $eNotification->getMessage(), [
                                'exception' => $eNotification,
                                'total'     => $totalCarrito,
                            ]);
                        }
                    });
                }

                if ($request->filled('redirect_to')) {
                    return redirect($request->input('redirect_to'))->with('success', '¡Ventas registradas con éxito!');
                }

                return redirect()->route('ventas.index')->with('success', '¡Ventas registradas con éxito!');
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::critical("Error transaccional en StoreMultiple Venta: " . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => Auth::id(),
            ]);
            return redirect()->back()->with('error', 'Error en base de datos al registrar las ventas.');
        }
    }

    /**
     * Muestra una venta específica.
     */
    public function show(Venta $venta): View
    {
        $venta->load(['user', 'comprador', 'articulo']);
        return view('ventas.show', compact('venta'));
    }

    /**
     * Muestra el formulario para editar una venta.
     */
    public function edit(int|string $id): View|RedirectResponse
    {
        try {
            $venta = Venta::with(['user', 'articulo'])->findOrFail($id);

            if (!Auth::user()->hasRole('admin') && $venta->user_id != Auth::id() && (int) $venta->comprador_id !== (int) Auth::id()) {
                abort(403, 'No tienes permiso para editar esta venta.');
            }

            $articulos = Articulo::whereNotIn('nombre', ['Pago saldado', 'saldo', 'Saldo', 'Abono', 'abono'])
                ->orderBy('nombre', 'asc')
                ->get();
            $clientes = User::orderBy('name', 'asc')->get();
        } catch (\Throwable $e) {
            Log::critical("Error en Edit Venta: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error en base de datos.');
        }

        return view('ventas.edit', compact('venta', 'articulos', 'clientes'));
    }

    /**
     * Actualiza una venta y ajusta el inventario proporcionalmente con bloqueo pesimista.
     */
    public function update(Request $request, Venta $venta): RedirectResponse
    {
        $validated = $request->validate([
            'articulo_id'  => 'required|exists:articulos,id',
            'cantidad'     => 'required|integer|min:1',
            'cliente_id'   => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion'  => 'nullable|string',
        ]);

        $authUser = Auth::user();
        if (!$authUser->hasRole('admin') && $venta->user_id !== $authUser->id && (int) $venta->comprador_id !== (int) $authUser->id) {
            abort(403, 'No tienes permiso para actualizar esta venta.');
        }

        try {
            return DB::transaction(function () use ($validated, $request, $venta, $authUser): RedirectResponse {
                $userId = ($authUser->hasRole('admin') && $request->filled('cliente_id'))
                    ? (int) $validated['cliente_id']
                    : (int) ($venta->user_id ?? $authUser->id);

                $cantidadAnterior = (int) $venta->cantidad;
                $nuevaCantidad = (int) $validated['cantidad'];

                // 1. Ajuste de stock si se cambia de artículo o de cantidad
                if ($venta->articulo_id != $validated['articulo_id']) {
                    $articuloAnterior = Articulo::lockForUpdate()->findOrFail($venta->articulo_id);
                    $articuloAnterior->increment('stock', $cantidadAnterior);

                    $nuevoArticulo = Articulo::lockForUpdate()->findOrFail($validated['articulo_id']);
                    if ($nuevoArticulo->stock < $nuevaCantidad) {
                        throw ValidationException::withMessages([
                            'cantidad' => "Stock insuficiente en el artículo seleccionado. Disponible: {$nuevoArticulo->stock}",
                        ]);
                    }
                    $nuevoArticulo->decrement('stock', $nuevaCantidad);
                } else {
                    $articulo = Articulo::lockForUpdate()->findOrFail($venta->articulo_id);
                    $diferencia = $nuevaCantidad - $cantidadAnterior;

                    if ($diferencia > 0) {
                        if ($articulo->stock < $diferencia) {
                            throw ValidationException::withMessages([
                                'cantidad' => "Stock insuficiente para incrementar la cantidad. Disponible: {$articulo->stock}",
                            ]);
                        }
                        $articulo->decrement('stock', $diferencia);
                    } elseif ($diferencia < 0) {
                        $articulo->increment('stock', abs($diferencia));
                    }
                }

                $total = ((float) $validated['precio_venta']) * $nuevaCantidad;

                // 2. Actualizar la Venta
                $venta->update([
                    'articulo_id'  => $validated['articulo_id'],
                    'cantidad'     => $nuevaCantidad,
                    'precio_venta' => $validated['precio_venta'],
                    'total_venta'  => $total,
                    'user_id'      => $userId,
                    'descripcion'  => $validated['descripcion'] ?? null,
                ]);

                // 3. Actualizar el Pedido vinculado si existe
                $pedidoExistente = Pedido::where('venta_id', $venta->id)->first();
                if ($pedidoExistente) {
                    $pedidoExistente->update([
                        'articulo_id' => $validated['articulo_id'],
                        'descripcion' => $validated['descripcion'] ?? '',
                        'costo'       => $total,
                        'cantidad'    => $nuevaCantidad,
                        'user_id'     => $userId,
                    ]);
                }

                return redirect()->route('ventas.index')->with('success', '¡Venta actualizada correctamente!');
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::critical("Error en Update Venta: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error en base de datos al actualizar la venta.');
        }
    }

    /**
     * Elimina una venta y restituye el stock en inventario.
     * Solo los administradores pueden ejecutar la eliminación física o anulación directa.
     */
    public function destroy(Venta $venta): RedirectResponse
    {
        if (Auth::user()->hasRole('vendedor')) {
            abort(403, 'Los vendedores no tienen autorización para eliminar ventas directamente.');
        }

        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada. Solo los administradores pueden eliminar o anular ventas directamente.');
        }

        try {
            DB::transaction(function () use ($venta): void {
                $articulo = Articulo::lockForUpdate()->find($venta->articulo_id);
                if ($articulo) {
                    $articulo->increment('stock', (int) $venta->cantidad);
                }

                Pedido::where('venta_id', $venta->id)->delete();
                $venta->delete();
            });

            return redirect()->route('ventas.index')->with('success', '¡Venta eliminada correctamente!');
        } catch (\Throwable $e) {
            Log::error("Error al eliminar venta: " . $e->getMessage());
            return redirect()->route('ventas.index')->with('error', 'Error al eliminar la venta.');
        }
    }

    /**
     * Procesa la solicitud de anulación de una venta realizada por un vendedor.
     * Encola el mensaje de WhatsApp al Administrador y emite una notificación interna.
     */
    public function solicitarAnulacion(Request $request, Venta $venta): RedirectResponse
    {
        $user = Auth::user();

        // 1. Autorización: El vendedor solo puede anular ventas que él mismo operó
        if ($user->hasRole('vendedor') && (int) $venta->comprador_id !== (int) $user->id) {
            abort(403, 'Solo puedes solicitar la anulación de ventas que tú mismo operaste.');
        }

        if (!$user->hasRole('admin') && !$user->hasRole('vendedor') && (int) $venta->comprador_id !== (int) $user->id) {
            abort(403, 'No autorizado para solicitar la anulación de esta venta.');
        }

        // 2. Validación estricta del motivo (mínimo 10 caracteres)
        $validated = $request->validate([
            'motivo' => 'required|string|min:10|max:1000',
        ]);

        $motivo = trim($validated['motivo']);

        // 3. Notificación al Administrador (interna y WhatsApp) de forma desacoplada y no bloqueante
        try {
            $venta->loadMissing(['user', 'comprador', 'articulo']);
            $vendedorNombre = $user->name;
            $clienteNombre  = $venta->user?->name ?? 'Cliente';
            $articuloNombre = $venta->articulo?->nombre ?? 'N/A';
            $totalFormatted = number_format((float) $venta->total_venta, 2);

            // Formato exacto requerido para WhatsApp
            $mensajeWa = "⚠️ SOLICITUD DE ANULACIÓN DE VENTA #{$venta->id}\n" .
                         "• Vendedor: {$vendedorNombre}\n" .
                         "• Cliente: {$clienteNombre}\n" .
                         "• Artículo: {$articuloNombre} (Total: \${$totalFormatted})\n" .
                         "• Motivo: \"{$motivo}\"";

            // Obtener números de teléfono de administradores registrados
            $adminPhones = DB::table('users')
                ->join('role_user', 'users.id', '=', 'role_user.user_id')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->whereIn(DB::raw('LOWER(roles.name)'), ['admin', 'administrador'])
                ->whereNotNull('users.telefono')
                ->where('users.telefono', '!=', '')
                ->pluck('users.telefono')
                ->map(function ($tel) {
                    $num = preg_replace('/[^0-9]/', '', (string) $tel);
                    if (strlen($num) === 10) return '521' . $num;
                    if (strlen($num) === 12 && str_starts_with($num, '52')) return '521' . substr($num, 2);
                    return $num;
                })
                ->filter()
                ->unique()
                ->toArray();

            if (empty($adminPhones)) {
                $adminPhones = ['5212222153410'];
            }

            foreach ($adminPhones as $telAdmin) {
                DB::table('whatsapp_pending_messages')->insert([
                    'numero'     => $telAdmin,
                    'mensaje'    => $mensajeWa,
                    'status'     => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info("Solicitud de anulación para venta #{$venta->id} encolada en WhatsApp para admins.");

            // Notificación Push/Web a Administradores
            try {
                \App\Services\PushNotificationService::notifyAdmins(
                    "⚠️ Solicitud de Anulación de Venta #{$venta->id}",
                    "El vendedor {$vendedorNombre} solicitó anular la venta #{$venta->id} (\${$totalFormatted}) del cliente {$clienteNombre}. Motivo: {$motivo}",
                    route('ventas.index')
                );
            } catch (\Throwable $ePush) {
                Log::error("Error enviando Push de anulación de venta #{$venta->id}: " . $ePush->getMessage());
            }

        } catch (\Throwable $eNotification) {
            Log::error("Fallo no bloqueante al procesar notificación de anulación de venta #{$venta->id}: " . $eNotification->getMessage(), [
                'exception' => $eNotification,
                'venta_id'  => $venta->id,
            ]);
        }

        return redirect()->route('ventas.index')->with('success', 'Solicitud enviada al Administrador para su revisión');
    }
}
