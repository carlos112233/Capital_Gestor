<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\User;
use App\Models\Articulo;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\NuevoPedidoNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    /**
     * Muestra el listado de ventas.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        // PostgreSQL es sensible a mayúsculas en búsquedas (LIKE vs ILIKE)
        $ventasQuery = Venta::with(['user', 'articulo'])->latest();

        if (!$user->hasRole('admin')) {
            $ventasQuery->where('user_id', $user->id);
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $ventasQuery->whereHas('user', function ($query) use ($search) {
                // ILIKE es específico de Postgres para búsquedas insensibles a mayúsculas
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $ventas = $ventasQuery->paginate(25)->withQueryString();

        $articulos = Articulo::comerciales()
            ->where('stock', '>', 0)
            ->orderBy('nombre', 'asc')
            ->get();
        $clientes = User::orderBy('name', 'asc')->get();

        if ($request->ajax()) {
            return view('ventas._tabla', compact('ventas', 'articulos', 'clientes'))->render();
        }
        return view('ventas.index', compact('ventas', 'articulos', 'clientes'));
    }

    /**
     * Muestra el formulario para crear una venta.
     */
    public function create(Request $request)
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
     * Almacena una nueva venta en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'articulo_id'  => 'required|exists:articulos,id',
            'cantidad'     => 'required|integer|min:1',
            'cliente_id'   => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion'  => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($validated, $request) {
                $articulo = Articulo::lockForUpdate()->findOrFail($validated['articulo_id']);
                $cantidadVenta = (int) $validated['cantidad'];

                // Validar stock disponible
                if ($articulo->stock < $cantidadVenta) {
                    throw ValidationException::withMessages([
                        'cantidad' => "Stock insuficiente. Disponible: {$articulo->stock}",
                    ]);
                }

                // 1. Determinar el ID del usuario cliente en la venta
                $finalUserId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
                    ? $validated['cliente_id']
                    : Auth::id();

                // 2. Descontar stock del artículo
                $articulo->decrement('stock', $cantidadVenta);

                // 3. Calcular total con base en el precio validado
                $totalVenta = ((float) $validated['precio_venta']) * $cantidadVenta;

                // 4. Crear la venta
                $venta = $articulo->ventas()->create([
                    'user_id'      => $finalUserId,
                    'cantidad'     => $cantidadVenta,
                    'precio_venta' => $validated['precio_venta'],
                    'total_venta'  => $totalVenta,
                    'descripcion'  => $validated['descripcion'] ?? null,
                ]);

                // Si la venta proviene de un pedido existente, vinculamos la venta al pedido
                if ($request->filled('pedido_id')) {
                    $pedido = Pedido::find($request->input('pedido_id'));
                    if ($pedido) {
                        $pedido->update(['venta_id' => $venta->id]);
                    }
                }

                return redirect()->route('ventas.index')->with('success', '¡Venta registrada con éxito!');
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::critical("Error en Store Venta: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error en base de datos al registrar la venta.');
        }
    }

    /**
     * Muestra una venta específica.
     */
    public function show(Venta $venta)
    {
        $venta->load(['user', 'articulo']);
        return view('ventas.show', compact('venta'));
    }

    /**
     * Muestra el formulario para editar una venta.
     */
    public function edit($id)
    {
        try {
            $venta = Venta::with(['user', 'articulo'])->findOrFail($id);

            // Verificar permisos: si no es admin, solo puede editar sus propias ventas
            if (!Auth::user()->hasRole('admin') && $venta->user_id != Auth::id()) {
                abort(403, 'No tienes permiso para editar esta venta.');
            }

            $articulos = Articulo::whereNotIn('nombre', ['Pago saldado', 'saldo', 'Saldo', 'Abono', 'abono'])
                ->orderBy('nombre', 'asc')
                ->get();
            $clientes  = User::orderBy('name', 'asc')->get();
        } catch (\Exception $e) {
            \Log::critical("Error en Edit Venta: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error en base de datos.');
        }

        return view('ventas.edit', compact('venta', 'articulos', 'clientes'));
    }

    /**
     * Actualiza una venta y ajusta el inventario proporcionalmente.
     */
    public function update(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'articulo_id'  => 'required|exists:articulos,id',
            'cantidad'     => 'required|integer|min:1',
            'cliente_id'   => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion'  => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($validated, $request, $venta) {
                $userId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
                    ? $validated['cliente_id']
                    : Auth::id();

                $cantidadAnterior = (int) $venta->cantidad;
                $nuevaCantidad = (int) $validated['cantidad'];

                // 1. Ajuste de stock si se cambia de artículo o de cantidad
                if ($venta->articulo_id != $validated['articulo_id']) {
                    // Restituir stock al artículo anterior
                    $articuloAnterior = Articulo::lockForUpdate()->findOrFail($venta->articulo_id);
                    $articuloAnterior->increment('stock', $cantidadAnterior);

                    // Descontar stock del nuevo artículo
                    $nuevoArticulo = Articulo::lockForUpdate()->findOrFail($validated['articulo_id']);
                    if ($nuevoArticulo->stock < $nuevaCantidad) {
                        throw ValidationException::withMessages([
                            'cantidad' => "Stock insuficiente en el artículo seleccionado. Disponible: {$nuevoArticulo->stock}",
                        ]);
                    }
                    $nuevoArticulo->decrement('stock', $nuevaCantidad);
                } else {
                    // Mismo artículo: ajustar diferencia de stock
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
        } catch (\Exception $e) {
            \Log::critical("Error en Update Venta: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error en base de datos al actualizar la venta.');
        }
    }

    /**
     * Elimina una venta y restituye el stock en inventario.
     */
    public function destroy(Venta $venta)
    {
        if (!Auth::user()->hasRole('admin') && $venta->user_id !== Auth::id()) {
            abort(403, 'No autorizado para eliminar esta venta.');
        }

        try {
            DB::transaction(function () use ($venta) {
                $articulo = Articulo::lockForUpdate()->find($venta->articulo_id);
                if ($articulo) {
                    $articulo->increment('stock', (int) $venta->cantidad);
                }

                // Eliminar pedido vinculado si existe
                Pedido::where('venta_id', $venta->id)->delete();
                $venta->delete();
            });

            return redirect()->route('ventas.index')->with('success', '¡Venta eliminada correctamente!');
        } catch (\Exception $e) {
            \Log::error("Postgres Delete Error: " . $e->getMessage());
            return redirect()->route('ventas.index')->with('error', 'Error al eliminar la venta.');
        }
    }
}

